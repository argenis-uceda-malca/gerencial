<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardGerencialController extends Controller
{
    public function index(Request $request)
    {
        $fechaInicio = $request->input('fecha_inicio', date('Y-m-01'));
        $fechaFin = $request->input('fecha_fin', date('Y-m-t'));
        $marca = $request->input('marca');

        $query = DB::connection('pgsql')
            ->table('automatizacion_pla_reporte_ventas')
            ->selectRaw('fecha_documento, sucursal_2, sucursal_3, marca, categoria,
                SUM(importe_subtotal) AS venta_neta,
                SUM(meta_venta) AS meta,
                ROUND(SUM(importe_subtotal) / NULLIF(SUM(meta_venta), 0) * 100, 1) AS pct_cumplimiento')
            ->where('tipo_fila', 'ventas_act')
            ->whereBetween('fecha_documento', [$fechaInicio, $fechaFin]);

        if ($marca) {
            $query->where('marca', $marca);
        }

        $resumen = $query
            ->groupBy('fecha_documento', 'sucursal_2', 'sucursal_3', 'marca', 'categoria')
            ->orderBy('fecha_documento')
            ->get();

        $marcas = DB::connection('pgsql')
            ->table('automatizacion_pla_reporte_ventas')
            ->where('tipo_fila', 'ventas_act')
            ->distinct()
            ->orderBy('marca')
            ->pluck('marca');

        return view('dashboard.gerencial', compact('resumen', 'marcas', 'fechaInicio', 'fechaFin', 'marca'));
    }
}
