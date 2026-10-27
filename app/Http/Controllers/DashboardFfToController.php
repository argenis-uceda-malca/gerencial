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
    // Vista pública: sin login ni chequeo de permisos (ver routes/web.php).

    /**
     * Tope de días que se backfillean en vivo contra la API de TB Retail
     * por request. Evita que una ventana con muchos días sin cargar
     * bloquee la respuesta HTTP por demasiado tiempo.
     */
    const MAX_DIAS_BACKFILL = 15;

    /**
     * Cobertura mínima de días con tráfico (respecto a los días con venta)
     * para que el FF de una tienda se considere confiable en un período.
     * Por debajo, el contador estuvo caído o no existía y comparar FF,
     * CR o TO/FF daría variaciones falsas.
     */
    const MIN_COBERTURA_FF = 0.9;

    /**
     * Días con venta mínimos de un período respecto al otro para que la
     * tienda sea comparable (like for like) y entre a los totales.
     */
    const MIN_COBERTURA_DIAS = 0.9;

    /** Rango máximo consultable (endpoint público). */
    const MAX_DIAS_RANGO = 370;

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
            ->map(function ($r) { return ['n' => (int) $r->mes_n, 'nom' => $r->mes]; })
            ->values()
            ->all();

        return view('dashboard.ff_to', [
            'meses'  => $meses,
            'anio'   => $today->year,
            'today'  => $today->toDateString(),
            'mesIni' => $today->copy()->startOfMonth()->toDateString(),
        ]);
    }

    /* ─────────────────────────────────────────────────────────
       DATOS — FF (tráfico) vs TO (ventas) por sucursal
    ───────────────────────────────────────────────────────── */
    public function data(Request $request, TbRetailService $tbRetailService)
    {
        $iniPedido = $this->fechaValida($request->input('ini'), Carbon::today()->startOfMonth());
        $finPedido = $this->fechaValida($request->input('fin'), Carbon::today());
        if ($iniPedido->gt($finPedido)) {
            $tmp = $iniPedido; $iniPedido = $finPedido; $finPedido = $tmp;
        }
        if ($iniPedido->diffInDays($finPedido) > self::MAX_DIAS_RANGO) {
            $iniPedido = $finPedido->copy()->subDays(self::MAX_DIAS_RANGO);
        }

        // Solo días cerrados: el día en curso tiene venta y tráfico parciales
        // y compararlo contra un día completo de 2025 sesga todo a la baja.
        $ini = $iniPedido->toDateString();
        $fin = $finPedido->copy()->min(Carbon::yesterday())->toDateString();
        if ($ini > $fin) {
            return response()->json($this->respuestaVacia($ini, $finPedido->toDateString(),
                'Todavía no hay días cerrados en este período.'));
        }

        $db = DB::connection('pgsql');

        // Si el ETL aún no cargó el último día del rango, recortar al último cargado.
        $maxAct = $db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->max('fecha_documento');
        if (!$maxAct) {
            return response()->json($this->respuestaVacia($ini, $fin, 'No hay ventas cargadas en este período.'));
        }
        $fin = min($fin, Carbon::parse($maxAct)->toDateString());

        // Días 2025 comparables según el calendario comercial
        // (pla_fechas_equivalentes). NO es un rango continuo: cada día 2025 se
        // asigna a un día 2026 conservando el mes (Semana Santa, día sobrante
        // del inicio de mes que pasa al final, etc.), igual que en el ETL.
        $fechasAnt = $this->fechasAnteriores($ini, $fin);

        // El bloque ventas_hst se carga con el mismo desfase que el actual
        // (fecha - 1 año calendario), así que puede no cubrir aún el último
        // día equivalente. Si falta, recortar el período actual para que
        // ambos lados tengan los mismos días.
        $maxHst = $db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'ventas_hst')
            ->whereBetween('fecha_documento', [reset($fechasAnt), end($fechasAnt)])
            ->max('fecha_documento');
        if ($maxHst && Carbon::parse($maxHst)->toDateString() < end($fechasAnt)) {
            $finRecorte = $db->table('pla_fechas_equivalentes')
                ->where('fecha', '<=', Carbon::parse($maxHst)->toDateString())
                ->whereBetween('fecha_equivalente', [$ini, $fin])
                ->max('fecha_equivalente');
            if ($finRecorte) {
                $fin = Carbon::parse($finRecorte)->toDateString();
                $fechasAnt = $this->fechasAnteriores($ini, $fin);
            }
        }
        $fechasAct = $this->diasEntre($ini, $fin);
        $iniAnt = reset($fechasAnt);
        $finAnt = end($fechasAnt);

        // Completar en vivo los días de tráfico que falten (ambos períodos).
        $this->asegurarConteos($tbRetailService, $fechasAct);
        $this->asegurarConteos($tbRetailService, $fechasAnt);

        // Venta actual: tipo_fila='ventas_act', montos en las columnas "normales".
        // Tickets = flag_tickets_act (1 por documento de venta, sin NC). OJO:
        // nro_tickets NO es un conteo, es el ROW_NUMBER de la línea dentro del
        // ticket; sumarlo infla los tickets según las líneas por ticket.
        $ventasAct = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("
                sucursal, MAX(sucursal_2_1) AS subcanal, MAX(sucursal_3_1) AS canal,
                SUM(importe_subtotal)            AS to_act,
                SUM(flag_tickets_act)            AS tickets_act,
                COUNT(DISTINCT fecha_documento)  AS dias,
                MAX(fecha_documento) - MIN(fecha_documento) + 1 AS ventana
            ")
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->groupBy('sucursal')
            ->having(DB::raw('SUM(importe_subtotal)'), '>', 0)
            ->get()
            ->keyBy('sucursal');

        // Venta comparativa: filas tipo_fila='ventas_hst'; el monto vive en las
        // columnas con sufijo "_1" y los tickets en flag_tickets_hst.
        $ventasHst = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("
                sucursal,
                SUM(importe_subtotal_hst_1)      AS to_hst,
                SUM(flag_tickets_hst)            AS tickets_hst,
                COUNT(DISTINCT fecha_documento)  AS dias,
                MAX(fecha_documento) - MIN(fecha_documento) + 1 AS ventana
            ")
            ->where('tipo_fila', 'ventas_hst')
            ->whereBetween('fecha_documento', [$iniAnt, $finAnt])
            ->whereIn('fecha_documento', $fechasAnt)
            ->groupBy('sucursal')
            ->having(DB::raw('SUM(importe_subtotal_hst_1)'), '>', 0)
            ->get()
            ->keyBy('sucursal');

        $ffAct = $this->conteosPorSucursal($fechasAct);
        $ffHst = $this->conteosPorSucursal($fechasAnt);

        $sucursales = [];
        foreach ($ventasAct as $sucursal => $v) {
            $hst = isset($ventasHst[$sucursal]) ? $ventasHst[$sucursal] : null;

            // FF solo cuenta si el contador cubrió casi todos los días con venta.
            $ffA = $this->ffConfiable(isset($ffAct[$sucursal]) ? $ffAct[$sucursal] : null, (int) $v->dias);
            $ffH = $hst !== null
                ? $this->ffConfiable(isset($ffHst[$sucursal]) ? $ffHst[$sucursal] : null, (int) $hst->dias)
                : null;

            $row = $this->buildRow(
                $sucursal,
                (string) $v->canal,
                (string) $v->subcanal,
                (float) $v->to_act,
                $hst !== null ? (float) $hst->to_hst : null,
                (float) $v->tickets_act,
                $hst !== null ? (float) $hst->tickets_hst : null,
                $ffA,
                $ffH
            );
            $row['tiene_contador'] = isset($ffAct[$sucursal]) || isset($ffHst[$sucursal]);
            // Comparable (like for like) = operó un tiempo equivalente en ambos
            // períodos. Una tienda que abrió, cerró o estuvo cerrada por
            // remodelación compara un período parcial contra uno completo
            // (p. ej. -99% por cierre). Se exige:
            //  - ventana (primera→última venta) equivalente: aperturas/cierres;
            //  - en tiendas físicas, además, días con venta equivalentes:
            //    cierres temporales. Online puede tener días sin venta por
            //    bajo volumen, así que ahí solo cuenta la ventana.
            $esWeb = strtoupper((string) $v->canal) === 'WEB';
            $row['dias_act'] = (int) ($esWeb ? $v->ventana : $v->dias);
            $row['dias_hst'] = $hst !== null ? (int) ($esWeb ? $hst->ventana : $hst->dias) : 0;
            $row['comparable'] = $hst !== null
                && $this->equivalentes((int) $v->ventana, (int) $hst->ventana)
                && ($esWeb || $this->equivalentes((int) $v->dias, (int) $hst->dias));
            $sucursales[] = $row;
        }

        usort($sucursales, function ($a, $b) { return $b['to_act'] <=> $a['to_act']; });

        // Tiendas con venta 2025 en el período equivalente pero sin venta hoy
        // (cerradas). Se excluyen del comparativo y se informan aparte.
        $cerradas = ['n' => 0, 'to_hst' => 0.0];
        foreach ($ventasHst as $sucursal => $h) {
            if (!isset($ventasAct[$sucursal])) {
                $cerradas['n']++;
                $cerradas['to_hst'] += (float) $h->to_hst;
            }
        }
        $cerradas['to_hst'] = round($cerradas['to_hst'], 2);

        return response()->json([
            'global'     => $this->buildGlobal($sucursales),
            'sucursales' => $sucursales,
            'meta'       => [
                'ini'            => $ini,
                'fin'            => $fin,
                'ini_ant'        => $iniAnt,
                'fin_ant'        => $finAnt,
                'fin_solicitado' => $finPedido->toDateString(),
                'cerradas'       => $cerradas,
                'dias_ant'       => count($fechasAnt),
                'dias_act'       => count($fechasAct),
                'dias_sin_ff'    => $this->diasSinConteo($fechasAct) + $this->diasSinConteo($fechasAnt),
            ],
        ]);
    }

    /**
     * KPIs globales "like for like":
     *  - TO / Tickets / ATV: tiendas comparables (venta en ambos períodos
     *    con días de operación equivalentes).
     *  - FF / CR / TO÷FF: además, con contador confiable en ambos períodos
     *    (si no, se mezclarían tickets y ventas de tiendas sin contador
     *    —online, outlets— contra un FF que no las incluye).
     */
    private function buildGlobal(array $sucursales): array
    {
        $comp = array_values(array_filter($sucursales, function ($r) { return $r['comparable']; }));
        $conFf = array_values(array_filter($comp, function ($r) { return $r['ff_act'] !== null && $r['ff_hst'] !== null; }));

        $toAct = array_sum(array_column($comp, 'to_act'));
        $toHst = array_sum(array_column($comp, 'to_hst'));
        $tkAct = array_sum(array_column($comp, 'tickets_act'));
        $tkHst = array_sum(array_column($comp, 'tickets_hst'));

        $ffAct   = array_sum(array_column($conFf, 'ff_act'));
        $ffHst   = array_sum(array_column($conFf, 'ff_hst'));
        $tkFfAct = array_sum(array_column($conFf, 'tickets_act'));
        $tkFfHst = array_sum(array_column($conFf, 'tickets_hst'));
        $toFfAct = array_sum(array_column($conFf, 'to_act'));
        $toFfHst = array_sum(array_column($conFf, 'to_hst'));

        $atvAct = $tkAct > 0 ? $toAct / $tkAct : null;
        $atvHst = $tkHst > 0 ? $toHst / $tkHst : null;
        $crAct  = $ffAct > 0 ? $tkFfAct / $ffAct * 100 : null;
        $crHst  = $ffHst > 0 ? $tkFfHst / $ffHst * 100 : null;
        $raAct  = $ffAct > 0 ? $toFfAct / $ffAct : null;
        $raHst  = $ffHst > 0 ? $toFfHst / $ffHst : null;

        return [
            'sucursal'    => 'TOTAL',
            'n_tiendas'   => count($sucursales),
            'n_comp'      => count($comp),
            'n_ff'        => count($conFf),
            'to_sin_comp' => round(array_sum(array_column($sucursales, 'to_act')) - $toAct, 2),

            'ff_act'      => $conFf ? round($ffAct, 0) : null,
            'ff_hst'      => $conFf ? round($ffHst, 0) : null,
            'ff_var'      => $this->pctVar($conFf ? $ffAct : null, $conFf ? $ffHst : null),

            'to_act'      => round($toAct, 2),
            'to_hst'      => round($toHst, 2),
            'to_var'      => $this->pctVar($toAct, $toHst),

            'tickets_act' => round($tkAct, 0),
            'tickets_hst' => round($tkHst, 0),
            'tickets_var' => $this->pctVar($tkAct, $tkHst),

            'atv_act'     => $atvAct !== null ? round($atvAct, 2) : null,
            'atv_hst'     => $atvHst !== null ? round($atvHst, 2) : null,
            'atv_var'     => $this->pctVar($atvAct, $atvHst),

            'cr_act'      => $crAct !== null ? round($crAct, 2) : null,
            'cr_hst'      => $crHst !== null ? round($crHst, 2) : null,

            'ratio_act'   => $raAct !== null ? round($raAct, 2) : null,
            'ratio_hst'   => $raHst !== null ? round($raHst, 2) : null,
            'gap'         => ($raAct !== null && $raHst !== null) ? round($raAct - $raHst, 2) : null,
        ];
    }

    private function respuestaVacia(string $ini, string $fin, string $aviso): array
    {
        return [
            'global'     => null,
            'sucursales' => [],
            'meta'       => ['ini' => $ini, 'fin' => $fin, 'aviso' => $aviso],
        ];
    }

    private function fechaValida($valor, Carbon $defecto): Carbon
    {
        if (is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
            try {
                return Carbon::createFromFormat('Y-m-d', $valor)->startOfDay();
            } catch (\Exception $e) {
                // cae al valor por defecto
            }
        }
        return $defecto->copy()->startOfDay();
    }

    /**
     * Días 2025 asignados a [ini, fin] en el calendario comercial
     * (pla_fechas_equivalentes), ordenados. Si la tabla no cubre el rango,
     * cae al mismo rango del año anterior.
     * @return string[]
     */
    private function fechasAnteriores(string $ini, string $fin): array
    {
        $fechas = DB::connection('pgsql')
            ->table('pla_fechas_equivalentes')
            ->whereBetween('fecha_equivalente', [$ini, $fin])
            ->selectRaw('DISTINCT fecha::date AS fecha')
            ->orderBy('fecha')
            ->pluck('fecha')
            ->map(function ($d) { return Carbon::parse($d)->toDateString(); })
            ->all();

        if (!$fechas) {
            $fechas = $this->diasEntre(Carbon::parse($ini)->subYear()->toDateString(), Carbon::parse($fin)->subYear()->toDateString());
        }
        return $fechas;
    }

    /** @return string[] días entre ini y fin, inclusive */
    private function diasEntre(string $ini, string $fin): array
    {
        $out = [];
        foreach (CarbonPeriod::create($ini, $fin) as $dia) {
            $out[] = $dia->toDateString();
        }
        return $out;
    }

    /**
     * Tráfico (tbretail_conteos, tipo='tienda') por sucursal: total y
     * cantidad de días con conteo > 0, para un conjunto de fechas.
     * @param string[] $fechas
     * @return array<string,array{ff:float,dias:int}>
     */
    private function conteosPorSucursal(array $fechas): array
    {
        if (!$fechas) return [];
        $rows = DB::connection('pgsql')
            ->table('tbretail_conteos as t')
            ->join('location_id_tienda as l', 'l.idtienda', '=', 't.entidad_id')
            ->join('automatizacion_dm_sucursales_activas as s', 's.idsucursal', '=', 'l.idtienda')
            ->where('t.tipo', 'tienda')
            ->where('t.conteo', '>', 0)
            ->whereBetween('t.fecha', [reset($fechas), end($fechas)])
            ->whereIn('t.fecha', $fechas)
            ->groupBy('s.sucursal')
            ->selectRaw('s.sucursal, SUM(t.conteo) AS ff, COUNT(DISTINCT t.fecha) AS dias')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[$r->sucursal] = ['ff' => (float) $r->ff, 'dias' => (int) $r->dias];
        }
        return $out;
    }

    private function equivalentes(int $a, int $b): bool
    {
        return $a > 0 && $b > 0 && min($a, $b) >= max($a, $b) * self::MIN_COBERTURA_DIAS;
    }

    /** FF del período si el contador cubrió suficientes días con venta; si no, null. */
    private function ffConfiable($conteo, int $diasVenta): ?float
    {
        if ($conteo === null || $diasVenta <= 0) return null;
        if ($conteo['dias'] < $diasVenta * self::MIN_COBERTURA_FF) return null;
        return $conteo['ff'];
    }

    /**
     * Fechas del conjunto (hasta ayer) sin ningún conteo de tráfico cargado.
     * @param string[] $fechas
     * @return string[]
     */
    private function fechasSinConteo(array $fechas): array
    {
        $ayer = Carbon::yesterday()->toDateString();
        $fechas = array_values(array_filter($fechas, function ($f) use ($ayer) { return $f <= $ayer; }));
        if (!$fechas) return [];

        $existentes = DB::connection('pgsql')
            ->table('tbretail_conteos')
            ->where('tipo', 'tienda')
            ->whereBetween('fecha', [reset($fechas), end($fechas)])
            ->selectRaw('DISTINCT fecha::date AS fecha')
            ->pluck('fecha')
            ->map(function ($d) { return Carbon::parse($d)->toDateString(); })
            ->all();

        return array_values(array_diff($fechas, $existentes));
    }

    /** @param string[] $fechas */
    private function diasSinConteo(array $fechas): int
    {
        return count($this->fechasSinConteo($fechas));
    }

    /**
     * Completa tbretail_conteos con los días del conjunto que todavía no
     * estén cargados, llamando a la API de TB Retail día por día
     * (hasta un tope) y persistiendo el resultado. Días futuros u hoy
     * (aún incompleto en la fuente) se omiten.
     * @param string[] $fechas
     */
    private function asegurarConteos(TbRetailService $tbRetailService, array $fechas): void
    {
        $faltantes = $this->fechasSinConteo($fechas);
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
        ?float $ffAct,
        ?float $ffHst
    ): array {
        $atvAct = $ticketsAct > 0 ? $toAct / $ticketsAct : null;
        $atvHst = ($ticketsHst !== null && $ticketsHst > 0) ? $toHst / $ticketsHst : null;
        $crAct  = ($ffAct !== null && $ffAct > 0) ? $ticketsAct / $ffAct * 100 : null;
        $crHst  = ($ffHst !== null && $ffHst > 0 && $ticketsHst !== null) ? $ticketsHst / $ffHst * 100 : null;
        $ratioAct = ($ffAct !== null && $ffAct > 0) ? $toAct / $ffAct : null;
        // Sin venta 2025 real para esta tienda (no hay fila 'ventas_hst' para el
        // rango) el ratio anterior queda indefinido, no en 0 — si no, el gap
        // siempre saldría positivo/falso "en alza" al restar contra cero.
        $ratioHst = ($ffHst !== null && $ffHst > 0 && $toHst !== null) ? $toHst / $ffHst : null;

        return [
            'sucursal'      => $sucursal,
            'canal'         => $canal,
            'subcanal'      => $subcanal,

            'ff_act'        => $ffAct !== null ? round($ffAct, 0) : null,
            'ff_hst'        => $ffHst !== null ? round($ffHst, 0) : null,
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
            // CR ya es un %, su variación se expresa en puntos porcentuales.
            'cr_var'        => ($crAct !== null && $crHst !== null) ? round($crAct - $crHst, 2) : null,

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
