<?php

namespace App\Http\Controllers;

use App\Models\Reporte_ventas;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;

class ReporteVentasController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        
        $datos = DB::table('datamart_ventas_actual')
            ->select('sucursal_marca', DB::raw('SUM(importe_subtotal) as importe_subtotal'), DB::raw('SUM(importe_impuesto) as importe_impuesto'), DB::raw('SUM(importe_total) as importe_total'))
            ->whereBetween('fecha_creacion', ['2023-01-01', '2023-01-02'])
            ->groupBy('sucursal_marca')
            ->get();

        return view('reporte_venta');

        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Reporte_ventas  $reporte_ventas
     * @return \Illuminate\Http\Response
     */
    public function show(Reporte_ventas $reporte_ventas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Reporte_ventas  $reporte_ventas
     * @return \Illuminate\Http\Response
     */
    public function edit(Reporte_ventas $reporte_ventas)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Reporte_ventas  $reporte_ventas
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Reporte_ventas $reporte_ventas)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Reporte_ventas  $reporte_ventas
     * @return \Illuminate\Http\Response
     */
    public function destroy(Reporte_ventas $reporte_ventas)
    {
        //
    }
}
