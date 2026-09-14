<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardGerencialController extends Controller
{
    public function index(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fechaFin    = $request->input('fecha_fin',    date('Y-m-d'));
        $marcaFiltro = $request->input('marca');
        $canalFiltro = $request->input('canal');

        $db = DB::connection('pgsql');

        // -- Opciones de filtros --
        $marcas = $db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'ventas_act')
            ->distinct()->orderBy('marca')->pluck('marca');

        $canales = $db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'ventas_act')
            ->whereNotNull('sucursal_2')
            ->distinct()->orderBy('sucursal_2')->pluck('sucursal_2');

        // -- Query base reutilizable --
        $base = fn () => $db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$fechaInicio, $fechaFin])
            ->when($marcaFiltro, fn ($q) => $q->where('marca', $marcaFiltro))
            ->when($canalFiltro, fn ($q) => $q->where('sucursal_2', $canalFiltro));

        // -- KPIs período actual --
        $kpi = $base()
            ->selectRaw('SUM(importe_subtotal) as venta, SUM(meta_venta) as meta, COUNT(DISTINCT sucursal_2) as canales')
            ->first();

        $ventaTotal = (float) ($kpi->venta ?? 0);
        $metaTotal  = (float) ($kpi->meta  ?? 0);
        $pctMeta    = $metaTotal > 0 ? round($ventaTotal / $metaTotal * 100, 1) : null;

        // -- KPI: mes anterior para variación --
        $diasPeriodo = (new \DateTime($fechaFin))->diff(new \DateTime($fechaInicio))->days + 1;
        $iniAnterior = date('Y-m-d', strtotime($fechaInicio . " -{$diasPeriodo} days"));
        $finAnterior = date('Y-m-d', strtotime($fechaInicio . ' -1 day'));

        $ventaAnterior = (float) ($db->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$iniAnterior, $finAnterior])
            ->when($marcaFiltro, fn ($q) => $q->where('marca', $marcaFiltro))
            ->when($canalFiltro, fn ($q) => $q->where('sucursal_2', $canalFiltro))
            ->sum('importe_subtotal') ?? 0);

        $variacion = $ventaAnterior > 0
            ? round(($ventaTotal - $ventaAnterior) / $ventaAnterior * 100, 1)
            : null;

        // -- Venta por marca --
        $porMarca = $base()
            ->selectRaw('marca, SUM(importe_subtotal) as venta, SUM(meta_venta) as meta')
            ->groupBy('marca')
            ->orderByDesc('venta')
            ->get();

        // -- Venta por canal --
        $porCanal = $base()
            ->selectRaw('sucursal_2 as canal, SUM(importe_subtotal) as venta, SUM(meta_venta) as meta')
            ->groupBy('sucursal_2')
            ->orderByDesc('venta')
            ->get();

        // -- Tendencia diaria --
        $tendencia = $base()
            ->selectRaw('fecha_documento, SUM(importe_subtotal) as venta, SUM(meta_venta) as meta')
            ->groupBy('fecha_documento')
            ->orderBy('fecha_documento')
            ->get();

        // -- Tabla detalle por canal × marca --
        $detalle = $base()
            ->selectRaw('sucursal_2 as canal, marca, SUM(importe_subtotal) as venta, SUM(meta_venta) as meta')
            ->groupBy('sucursal_2', 'marca')
            ->orderBy('sucursal_2')->orderByDesc('venta')
            ->get()
            ->groupBy('canal');

        return view('dashboard.gerencial', compact(
            'fechaInicio', 'fechaFin', 'marcaFiltro', 'canalFiltro',
            'marcas', 'canales',
            'ventaTotal', 'metaTotal', 'pctMeta', 'variacion', 'ventaAnterior',
            'porMarca', 'porCanal', 'tendencia', 'detalle'
        ));
    }
}
