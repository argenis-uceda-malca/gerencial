<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardVentasController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!in_array('acceso_dashboard_ventas', session('permisos', []))) {
                return redirect('/')->with('error', 'No tienes permiso para acceder.');
            }
            return $next($request);
        });
    }
    private $marcaLabel = [
        'MENTHA & CHOCOLATE' => 'MCH',
        'BLUES BY MILK'      => 'BBM',
        'EXIT'               => 'EXIT',
        'MILK'               => 'MILK',
        'FINA'               => 'FINA',
        'KORDA'              => 'KORDA',
        'JOIN'               => 'JOIN',
        'SIN MARCA'          => 'S/M',
    ];

    private function canonicalCanal(string $s3): string
    {
        return in_array($s3, ['WEB', 'ECOMMERCE OFF']) ? 'WEB' : $s3;
    }

    public function index(Request $request)
    {
        $today  = Carbon::today();
        $ini    = $today->copy()->startOfYear()->toDateString();
        $mesIni = $today->copy()->startOfMonth()->toDateString();
        $mesFin = $today->copy()->endOfMonth()->toDateString();

        $rows    = $this->buildFlatRows($ini, $today->toDateString());
        $tiendas = $this->buildTiendas($mesIni, $mesFin);
        $ff      = $this->buildFF($mesIni, $mesFin);

        $meses = collect($rows)
            ->groupBy('mes_n')
            ->map(fn($g) => ['n' => $g[0]['mes_n'], 'nom' => $g[0]['mes']])
            ->sortBy('n')
            ->values()
            ->all();

        return view('dashboard.ventas', [
            'rows'     => $rows,
            'tiendas'  => $tiendas,
            'meses'    => $meses,
            'ff'       => $ff,
            'today'    => $today->toDateString(),
            'iniAnual' => $ini,
            'mesIni'   => $mesIni,
        ]);
    }

    private function buildFlatRows(string $ini, string $fin): array
    {
        $db = DB::connection('pgsql');

        $ventas = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("
                fecha_documento::date                        AS fecha,
                dia_equivalente::int                         AS dia_eq,
                mes,
                EXTRACT(MONTH FROM fecha_documento)::int     AS mes_n,
                marca,
                sucursal_3,
                SUM(importe_subtotal)                        AS venta,
                SUM(importe_subtotal - costo_venta_neta)     AS util
            ")
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->groupBy(DB::raw("
                fecha_documento::date, dia_equivalente, mes,
                EXTRACT(MONTH FROM fecha_documento), marca, sucursal_3
            "))
            ->get();

        $metas = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("fecha_documento::date AS fecha, marca, sucursal_3, SUM(meta_venta) AS meta")
            ->where('tipo_fila', 'metas_std')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->groupBy(DB::raw("fecha_documento::date, marca, sucursal_3"))
            ->get()
            ->keyBy(fn($r) => $r->fecha . '|' . $r->marca . '|' . $this->canonicalCanal($r->sucursal_3));

        // Marcar metas ya usadas; las sobrantes (días sin ventas) se agregan como filas venta=0
        $usedMetaKeys = [];
        $rows = [];
        foreach ($ventas as $r) {
            $canal = $this->canonicalCanal($r->sucursal_3);
            $key   = $r->fecha . '|' . $r->marca . '|' . $canal;
            $usedMetaKeys[$key] = true;
            $venta = (float) $r->venta;
            $util  = (float) $r->util;
            $meta  = (float) ($metas[$key]->meta ?? 0);

            $rows[] = [
                'fecha'  => $r->fecha,
                'dia_eq' => (int) $r->dia_eq,
                'mes'    => $r->mes,
                'mes_n'  => (int) $r->mes_n,
                'marca'  => $this->marcaLabel[$r->marca] ?? $r->marca,
                'canal'  => $canal,
                'venta'  => round($venta, 2),
                'util'   => round($util, 2),
                'meta'   => round($meta, 2),
            ];
        }

        // Agregar metas huérfanas (días con meta pero sin ventas) para no perder esos montos
        foreach ($metas as $key => $m) {
            if (isset($usedMetaKeys[$key])) continue;
            [$fecha, $marca, $canal] = explode('|', $key, 3);
            // Necesitamos mes/mes_n: buscamos en ventas del mismo mes o calculamos desde la fecha
            $dt = \Carbon\Carbon::parse($fecha);
            $mesNombres = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
                           'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            $rows[] = [
                'fecha'  => $fecha,
                'dia_eq' => (int) $dt->day,
                'mes'    => $mesNombres[$dt->month],
                'mes_n'  => $dt->month,
                'marca'  => $this->marcaLabel[$marca] ?? $marca,
                'canal'  => $canal,
                'venta'  => 0.0,
                'util'   => 0.0,
                'meta'   => round((float) $m->meta, 2),
            ];
        }

        usort($rows, fn($a, $b) => strcmp($a['fecha'], $b['fecha']));
        return $rows;
    }

    public function tiendas(Request $request): \Illuminate\Http\JsonResponse
    {
        $ini     = $request->input('ini', Carbon::today()->startOfMonth()->toDateString());
        $fin     = $request->input('fin', Carbon::today()->toDateString());
        $canales = $request->input('canales', []);
        $meses   = array_map('intval', $request->input('meses', []));
        $dias    = array_map('intval', $request->input('dias', []));

        $db = DB::connection('pgsql');

        // Expandir canal WEB → incluye ECOMMERCE OFF en DB
        $dbCanales = [];
        foreach ($canales as $c) {
            if ($c === 'WEB') { $dbCanales[] = 'WEB'; $dbCanales[] = 'ECOMMERCE OFF'; }
            else $dbCanales[] = $c;
        }

        $vQuery = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("sucursal, sucursal_3, marca,
                SUM(importe_subtotal)                    AS venta,
                SUM(importe_subtotal - costo_venta_neta) AS util")
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$ini, $fin]);

        if ($dbCanales) $vQuery->whereIn('sucursal_3', $dbCanales);
        if ($meses)     $vQuery->whereRaw('EXTRACT(MONTH FROM fecha_documento)::int IN (' . implode(',', $meses) . ')');
        if ($dias)      $vQuery->whereRaw('dia_equivalente::int IN (' . implode(',', $dias) . ')');

        $ventas = $vQuery->groupBy('sucursal', 'sucursal_3', 'marca')
            ->orderByDesc(DB::raw('SUM(importe_subtotal)'))
            ->get();

        $mQuery = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("sucursal, sucursal_3, marca, SUM(meta_venta) AS meta")
            ->where('tipo_fila', 'metas_std')
            ->whereBetween('fecha_documento', [$ini, $fin]);

        if ($dbCanales) $mQuery->whereIn('sucursal_3', $dbCanales);
        if ($meses) $mQuery->whereRaw('EXTRACT(MONTH FROM fecha_documento)::int IN (' . implode(',', $meses) . ')');
        if ($dias)  $mQuery->whereRaw('dia_equivalente::int IN (' . implode(',', $dias) . ')');

        $metas = $mQuery->groupBy('sucursal', 'sucursal_3', 'marca')
            ->get()
            ->keyBy(fn($r) => $r->sucursal . '|' . $r->sucursal_3 . '|' . $r->marca);

        $rows = [];
        foreach ($ventas as $r) {
            $key  = $r->sucursal . '|' . $r->sucursal_3 . '|' . $r->marca;
            $canal = $this->canonicalCanal($r->sucursal_3);
            $venta = (float) $r->venta;
            $util  = (float) $r->util;
            $meta  = (float) (($metas[$key] ?? null)?->meta ?? 0);
            unset($metas[$key]); // remove matched, remaining are orphans
            $pct   = $meta > 0 ? round($venta / $meta * 100, 1) : 0;
            $gm    = $venta > 0 ? round($util / $venta * 100, 1) : 0;
            $var   = $meta > 0 ? round(($venta - $meta) / $meta * 100, 1) : 0;

            $rows[] = [
                'sucursal' => $r->sucursal,
                'canal'    => $canal,
                'marca'    => $this->marcaLabel[$r->marca] ?? $r->marca,
                'venta'    => round($venta, 2),
                'meta'     => round($meta, 2),
                'pct'      => $pct,
                'util'     => round($util, 2),
                'gm'       => $gm,
                'var'      => $var,
            ];
        }

        // Agregar metas huérfanas (sin ventas en el período)
        foreach ($metas as $key => $m) {
            $parts = explode('|', $key, 3);
            if (count($parts) !== 3) continue;
            [$sucursal, $s3, $marca] = $parts;
            $meta = (float) $m->meta;
            $rows[] = [
                'sucursal' => $sucursal,
                'canal'    => $this->canonicalCanal($s3),
                'marca'    => $this->marcaLabel[$marca] ?? $marca,
                'venta'    => 0.0,
                'meta'     => round($meta, 2),
                'pct'      => 0,
                'util'     => 0.0,
                'gm'       => 0,
                'var'      => 0,
            ];
        }

        return response()->json($rows);
    }

    public function topProductos(Request $request): \Illuminate\Http\JsonResponse
    {
        $ini     = $request->input('ini', Carbon::today()->startOfYear()->toDateString());
        $fin     = $request->input('fin', Carbon::today()->toDateString());
        $canales = $request->input('canales', []);
        $meses   = array_map('intval', $request->input('meses', []));
        $dias    = array_map('intval', $request->input('dias', []));

        $db = DB::connection('pgsql');

        $dbCanales = [];
        foreach ($canales as $c) {
            if ($c === 'WEB') { $dbCanales[] = 'WEB'; $dbCanales[] = 'ECOMMERCE OFF'; }
            else $dbCanales[] = $c;
        }

        $query = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("codigo_padre,
                COALESCE(NULLIF(TRIM(descripcion_padre), ''), 'SIN DESCRIPCIÓN') AS descripcion_padre,
                marca,
                SUM(importe_subtotal) AS total_venta,
                SUM(unidades) AS total_unidades")
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->whereNotNull('codigo_padre')
            ->where('codigo_padre', '!=', '');

        if ($dbCanales) $query->whereIn('sucursal_3', $dbCanales);
        if ($meses)     $query->whereRaw('EXTRACT(MONTH FROM fecha_documento)::int IN (' . implode(',', $meses) . ')');
        if ($dias)      $query->whereRaw('dia_equivalente::int IN (' . implode(',', $dias) . ')');

        $result = $query->groupBy('codigo_padre', 'descripcion_padre', 'marca')
            ->orderByDesc(DB::raw('SUM(importe_subtotal)'))
            ->limit(10)
            ->get();

        return response()->json($result);
    }

    public function rows(Request $request): \Illuminate\Http\JsonResponse
    {
        $ini = $request->input('ini', Carbon::today()->startOfYear()->toDateString());
        $fin = $request->input('fin', Carbon::today()->toDateString());

        $rows = $this->buildFlatRows($ini, $fin);
        $ff   = $this->buildFF(Carbon::today()->startOfMonth()->toDateString(), $fin);

        return response()->json(['rows' => $rows, 'ff' => $ff]);
    }

    private function buildTiendas(string $ini, string $fin): array
    {
        $db = DB::connection('pgsql');

        $ventas = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("sucursal, sucursal_3, marca,
                SUM(importe_subtotal)                    AS venta,
                SUM(importe_subtotal - costo_venta_neta) AS util")
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->groupBy('sucursal', 'sucursal_3', 'marca')
            ->orderByDesc(DB::raw('SUM(importe_subtotal)'))
            ->get();

        $metas = $db->table('automatizacion_pla_reporte_ventas')
            ->selectRaw("sucursal, sucursal_3, marca, SUM(meta_venta) AS meta")
            ->where('tipo_fila', 'metas_std')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->groupBy('sucursal', 'sucursal_3', 'marca')
            ->get()
            ->keyBy(fn($r) => $r->sucursal . '|' . $r->sucursal_3 . '|' . $r->marca);

        $rows = [];
        foreach ($ventas as $r) {
            $key  = $r->sucursal . '|' . $r->sucursal_3 . '|' . $r->marca;
            $canal = $this->canonicalCanal($r->sucursal_3);
            $venta = (float) $r->venta;
            $util  = (float) $r->util;
            $meta  = (float) (($metas[$key] ?? null)?->meta ?? 0);
            unset($metas[$key]);
            $pct   = $meta > 0 ? round($venta / $meta * 100, 1) : 0;
            $gm    = $venta > 0 ? round($util / $venta * 100, 1) : 0;
            $var   = $meta > 0 ? round(($venta - $meta) / $meta * 100, 1) : 0;

            $rows[] = [
                'sucursal' => $r->sucursal,
                'canal'    => $canal,
                'marca'    => $this->marcaLabel[$r->marca] ?? $r->marca,
                'venta'    => round($venta, 2),
                'meta'     => round($meta, 2),
                'pct'      => $pct,
                'util'     => round($util, 2),
                'gm'       => $gm,
                'var'      => $var,
            ];
        }

        foreach ($metas as $key => $m) {
            $parts = explode('|', $key, 3);
            if (count($parts) !== 3) continue;
            [$sucursal, $s3, $marca] = $parts;
            $meta = (float) $m->meta;
            $rows[] = [
                'sucursal' => $sucursal,
                'canal'    => $this->canonicalCanal($s3),
                'marca'    => $this->marcaLabel[$marca] ?? $marca,
                'venta'    => 0.0,
                'meta'     => round($meta, 2),
                'pct'      => 0,
                'util'     => 0.0,
                'gm'       => 0,
                'var'      => 0,
            ];
        }

        return $rows;
    }

    private function buildFF(string $ini, string $fin): array
    {
        $db   = DB::connection('pgsql');
        $act  = (float) $db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'poken_act')
            ->whereBetween('fecha_documento', [$ini, $fin])
            ->sum('conteo');
        $equiv = DB::connection('pgsql')
            ->table('pla_fechas_equivalentes')
            ->whereBetween('fecha_equivalente', [$ini, $fin])
            ->selectRaw('MIN(fecha) AS ini_ant, MAX(fecha) AS fin_ant')
            ->first();
        $iniH = $equiv->ini_ant ?? Carbon::parse($ini)->subYear()->toDateString();
        $finH = $equiv->fin_ant ?? Carbon::parse($fin)->subYear()->toDateString();
        $hst  = (float) $db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'poken_hst')
            ->whereBetween('fecha_documento', [$iniH, $finH])
            ->sum('conteo_hst');

        return [
            'act' => (int) $act,
            'hst' => (int) $hst,
            'var' => $hst > 0 ? round(($act - $hst) / $hst * 100, 1) : 0,
        ];
    }
}
