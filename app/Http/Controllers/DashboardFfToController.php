<?php

namespace App\Http\Controllers;

use App\Services\TbRetailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DashboardFfToController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!in_array('acceso_administrador', session('permisos', []))) {
                return redirect('/')->with('error', 'No tienes permiso para acceder.');
            }
            return $next($request);
        });
    }
    /**
     * Tope de días que se backfillean en vivo contra la API de TB Retail
     * por request. Evita que una ventana con muchos días sin cargar
     * bloquee la respuesta HTTP por demasiado tiempo.
     */
    private const MAX_DIAS_BACKFILL = 15;

    public function index(Request $request)
    {
        $today = Carbon::today();

        $meses = DB::connection('pgsql')
            ->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("mes, EXTRACT(MONTH FROM fecha_documento)::int AS mes_n")
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$today->copy()->startOfYear()->toDateString(), $today->toDateString()])
            ->groupBy(DB::raw("mes, EXTRACT(MONTH FROM fecha_documento)"))
            ->orderBy(DB::raw("EXTRACT(MONTH FROM fecha_documento)"))
            ->get()
            ->map(fn($r) => ['n' => (int) $r->mes_n, 'nom' => $r->mes])
            ->values()
            ->all();

        return view('dashboard.ff_to', [
            'meses'  => $meses,
            'today'  => $today->toDateString(),
            'mesIni' => $today->copy()->startOfMonth()->toDateString(),
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       DATOS — FF (tráfico) vs TO (ventas) por sucursal
    ───────────────────────────────────────────────────────── */
    public function data(Request $request, TbRetailService $tbRetailService)
    {
        $ini = $request->input('ini', Carbon::today()->startOfMonth()->toDateString());
        $fin = $request->input('fin', Carbon::today()->toDateString());

        $equiv = DB::connection('pgsql')
            ->table('pla_fechas_equivalentes')
            ->whereBetween('fecha_equivalente', [$ini, $fin])
            ->selectRaw('MIN(fecha) AS ini_ant, MAX(fecha) AS fin_ant')
            ->first();
        $iniAnt = $equiv->ini_ant ?? Carbon::parse($ini)->subYear()->toDateString();
        $finAnt = $equiv->fin_ant ?? Carbon::parse($fin)->subYear()->toDateString();

        // El período actual puede incluir días muy recientes que el ETL/tbretail
        // todavía no tiene cargados. Los completamos en vivo contra la API,
        // guardando el resultado para que la próxima vez ya esté en caché.
        $this->asegurarConteos($tbRetailService, $ini, $fin);

        $db = DB::connection('pgsql');

        // Venta actual (2026): tipo_fila='ventas_act', montos en las columnas "normales".
        $ventasAct = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("
                sucursal, sucursal_2_1 AS subcanal, sucursal_3_1 AS canal,
                SUM(importe_subtotal) AS to_act,
                SUM(nro_tickets)      AS tickets_act
            ")
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->groupBy('sucursal', 'sucursal_2_1', 'sucursal_3_1')
            ->having(DB::raw('SUM(importe_subtotal)'), '>', 0)
            ->get()
            ->keyBy('sucursal');

        // Venta comparativa (2025): filas aparte con tipo_fila='ventas_hst'; el monto
        // real vive en las columnas con sufijo "_1" (importe_subtotal_hst_1, nro_tickets_hst_1),
        // no en importe_subtotal_hst (esa queda en 0 para este bloque).
        $ventasHst = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("
                sucursal,
                SUM(importe_subtotal_hst_1) AS to_hst,
                SUM(nro_tickets_hst_1)      AS tickets_hst
            ")
            ->where('tipo_fila', 'ventas_hst')
            ->whereBetween('fecha_documento', [$iniAnt, $finAnt])
            ->groupBy('sucursal')
            ->having(DB::raw('SUM(importe_subtotal_hst_1)'), '>', 0)
            ->get()
            ->keyBy('sucursal');

        $ffAct = $this->conteosPorSucursal($ini, $fin);
        $ffHst = $this->conteosPorSucursal($iniAnt, $finAnt);

        $sucursales = [];
        foreach ($ventasAct as $sucursal => $v) {
            $hst = $ventasHst[$sucursal] ?? null;
            $sucursales[] = $this->buildRow(
                $sucursal,
                $v->canal,
                $v->subcanal,
                (float) $v->to_act,
                $hst !== null ? (float) $hst->to_hst : null,
                (float) $v->tickets_act,
                $hst !== null ? (float) $hst->tickets_hst : null,
                (float) ($ffAct[$sucursal] ?? 0),
                (float) ($ffHst[$sucursal] ?? 0)
            );
        }

        usort($sucursales, fn($a, $b) => $b['to_act'] <=> $a['to_act']);

        $global = $this->buildRow(
            'TOTAL',
            '',
            '',
            array_sum(array_column($sucursales, 'to_act')),
            array_sum(array_column($sucursales, 'to_hst')),
            array_sum(array_column($sucursales, 'tickets_act')),
            array_sum(array_column($sucursales, 'tickets_hst')),
            array_sum(array_column($sucursales, 'ff_act')),
            array_sum(array_column($sucursales, 'ff_hst'))
        );

        return response()->json([
            'global'     => $global,
            'sucursales' => $sucursales,
        ]);
    }

    /**
     * Suma de tráfico (tbretail_conteos, tipo='tienda') agrupado por
     * nombre de sucursal, para un rango de fechas.
     * @return array<string,float>
     */
    private function conteosPorSucursal(string $ini, string $fin): array
    {
        return DB::connection('pgsql')
            ->table('tbretail_conteos as t')
            ->join('location_id_tienda as l', 'l.idtienda', '=', 't.entidad_id')
            ->join('automatizacion_dm_sucursales_activas as s', 's.idsucursal', '=', 'l.idtienda')
            ->where('t.tipo', 'tienda')
            ->whereBetween('t.fecha', [$ini, $fin])
            ->groupBy('s.sucursal')
            ->selectRaw('s.sucursal, SUM(t.conteo) AS ff')
            ->pluck('ff', 'sucursal')
            ->map(fn($v) => (float) $v)
            ->all();
    }

    /**
     * Completa tbretail_conteos con los días del rango que todavía no
     * estén cargados, llamando a la API de TB Retail día por día
     * (hasta un tope) y persistiendo el resultado. Días futuros u hoy
     * (aún incompleto en la fuente) se omiten.
     */
    private function asegurarConteos(TbRetailService $tbRetailService, string $ini, string $fin): void
    {
        $limite = Carbon::yesterday();
        $finReal = Carbon::parse($fin)->min($limite);
        if (Carbon::parse($ini)->gt($finReal)) return;

        $existentes = DB::connection('pgsql')
            ->table('tbretail_conteos')
            ->where('tipo', 'tienda')
            ->whereBetween('fecha', [$ini, $finReal->toDateString()])
            ->selectRaw('DISTINCT fecha::date AS fecha')
            ->pluck('fecha')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->all();

        $faltantes = [];
        foreach (CarbonPeriod::create($ini, $finReal) as $dia) {
            $f = $dia->toDateString();
            if (!in_array($f, $existentes, true)) $faltantes[] = $f;
        }

        if (!$faltantes) return;

        foreach (array_slice($faltantes, 0, self::MAX_DIAS_BACKFILL) as $f) {
            try {
                $tbRetailService->guardarConteosTbRetail('tienda', $f);
            } catch (\Exception $e) {
                Log::warning("FF/TO: no se pudo backfillear tráfico de {$f}: " . $e->getMessage());
            }
        }
    }

    private function buildRow(
        string $sucursal,
        string $canal,
        string $subcanal,
        float $toAct,
        ?float $toHst,
        float $ticketsAct,
        ?float $ticketsHst,
        float $ffAct,
        float $ffHst
    ): array {
        $atvAct = $ticketsAct > 0 ? $toAct / $ticketsAct : null;
        $atvHst = ($ticketsHst !== null && $ticketsHst > 0) ? $toHst / $ticketsHst : null;
        $crAct  = $ffAct > 0 ? $ticketsAct / $ffAct * 100 : null;
        $crHst  = ($ffHst > 0 && $ticketsHst !== null) ? $ticketsHst / $ffHst * 100 : null;
        $ratioAct = $ffAct > 0 ? $toAct / $ffAct : null;
        // Sin venta 2025 real para esta tienda (no hay fila 'ventas_hst' para el
        // rango) el ratio anterior queda indefinido, no en 0 — si no, el gap
        // siempre saldría positivo/falso "en alza" al restar contra cero.
        $ratioHst = ($ffHst > 0 && $toHst !== null) ? $toHst / $ffHst : null;

        return [
            'sucursal'      => $sucursal,
            'canal'         => $canal,
            'subcanal'      => $subcanal,

            'ff_act'        => round($ffAct, 0),
            'ff_hst'        => round($ffHst, 0),
            'ff_var'        => $this->pctVar($ffAct, $ffHst),

            'to_act'        => round($toAct, 2),
            'to_hst'        => $toHst !== null ? round($toHst, 2) : null,
            'to_var'        => $this->pctVar($toAct, $toHst),

            'tickets_act'   => round($ticketsAct, 0),
            'tickets_hst'   => $ticketsHst !== null ? round($ticketsHst, 0) : null,
            'tickets_var'   => $this->pctVar($ticketsAct, $ticketsHst),

            'atv_act'       => $atvAct !== null ? round($atvAct, 2) : null,
            'atv_hst'       => $atvHst !== null ? round($atvHst, 2) : null,
            'atv_var'       => $this->pctVar($atvAct, $atvHst),

            'cr_act'        => $crAct !== null ? round($crAct, 2) : null,
            'cr_hst'        => $crHst !== null ? round($crHst, 2) : null,
            'cr_var'        => $this->pctVar($crAct, $crHst),

            'ratio_act'     => $ratioAct !== null ? round($ratioAct, 2) : null,
            'ratio_hst'     => $ratioHst !== null ? round($ratioHst, 2) : null,
            'gap'           => ($ratioAct !== null && $ratioHst !== null) ? round($ratioAct - $ratioHst, 2) : null,
            'gap_pct'       => ($ratioAct !== null && $ratioHst !== null) ? $this->pctVar($ratioAct, $ratioHst) : null,
        ];
    }

    private function pctVar(?float $act, ?float $hst): ?float
    {
        if ($act === null || $hst === null || $hst == 0.0) return null;
        return round(($act - $hst) / abs($hst) * 100, 1);
    }
}
