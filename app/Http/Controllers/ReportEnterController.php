<?php

namespace App\Http\Controllers;


use App\Models\Report_enter;
use Illuminate\Http\Request;
use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

class ReportEnterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $fecha_inicio =  "'" . date('Y-m-d') . "'";
        $fecha_fin = "'" . date('Y-m-d') . "'";

        $marcas = $this->getMarcas();
        $reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin,  $this->getIDMarcas($marcas));
        $datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);

        //$idmarcas = $this->getIDMarcas($marcas);
        //var_dump($idmarcas);
        return view('reporte_venta', compact('datos', 'marcas', 'reporte'));
    }

    public function submitForm(Request $request)
    {

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $fecha_inicio = $request->input('fecha_inicio');
            $fecha_fin = $request->input('fecha_fin');

            $marcas_seleccionadas = array();
            $marcas_seleccionadas = $request->input('marca', []);
        }
        $marcas = $this->getMarcas();

        if ($request->filled('select_radio') && $request->input('select_radio') == 1) {
            //var_dump($marcas_seleccionadas);
            $reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $marcas_seleccionadas);
        } else {
            $reporte = $this->getReporteVentaTienda($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas));
            //$metas = $this->getMetastienda($reporte);
        }

        $datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);

        return view('reporte_venta', compact('datos', 'marcas', 'reporte'));
    }

    public function getDatosVentas($fecha_inicio, $fecha_fin)
    {
        $datos = DB::table('datamart_ventas_actual')
            ->select('sucursal_marca', DB::raw('SUM(cantidad) as cantidad'), DB::raw('SUM(importe_subtotal) as importe_subtotal'), DB::raw('SUM(importe_impuesto) as importe_impuesto'), DB::raw('SUM(importe_total) as importe_total'))
            ->whereBetween('fecha_creacion', [$fecha_inicio, $fecha_fin])
            ->groupBy('sucursal_marca')
            ->get();

        return $datos;
    }
    /*Esta es la funcion getReporteMarcaVenta, está bien?*/
    public function getReporteMarcaVenta($fecha_inicio, $fecha_fin, $marcas_select)
    {
        $bi_conexion = DB::connection('pgsql2');
        // Quiero extraer los valores de la columna nombres 
        $resultados = $bi_conexion->table('reporting_datamartventasclon')
            ->select('reporting_marcas.marca as nombre', 'reporting_datamartventasclon.sucursal_marca_id', DB::raw('SUM(reporting_datamartventasclon.importe_subtotal) AS importe_total_sum'))
            ->join('reporting_marcas', 'reporting_datamartventasclon.sucursal_marca_id', '=', 'reporting_marcas.id')
            ->whereNotNull('reporting_marcas.idmarca')
            ->whereBetween('reporting_datamartventasclon.fecha_documento', [$fecha_inicio, $fecha_fin])
            ->whereIn('reporting_datamartventasclon.sucursal_marca_id', $marcas_select)
            ->groupBy('reporting_marcas.marca', 'reporting_datamartventasclon.sucursal_marca_id')
            ->orderByDesc('importe_total_sum')
            ->get();

        // $query = $bi_conexion->table('reporting_datamartventasclon as dv')
        //     ->select(
        //         'rm.marca as nombre',
        //         DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
        //         DB::raw('MAX(vc.meta) AS meta'),
        //         DB::raw('SUM(dv.importe_subtotal) / MAX(vc.meta) * 100 AS logro'),
        //         DB::raw('(SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / SUM(dv.importe_subtotal) AS gm'),
        //         DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
        //         DB::raw('MAX(vc.meta_contribucion) AS meta_contribucion')
        //     )
        //     ->join(
        //         DB::raw('(SELECT vcc.sucursal, SUM(vcc.importe) AS meta, SUM(vcc.valor_contribucion) AS meta_contribucion 
        //             FROM reporting_datamartventascuotaclon vcc 
        //             WHERE vcc.fecha BETWEEN ? AND ? 
        //             GROUP BY vcc.sucursal) vc'),'dv.sucursal','=','vc.sucursal'
        //     )
        //     ->join('reporting_marcas as rm', 'dv.sucursal_marca_id', '=', 'rm.id')
        //     ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
        //     ->whereIn('dv.sucursal_marca_id', [$marcas_select])
        //     ->groupBy('rm.marca')
        //     ->orderBy('importe_total_sum', 'ASC');

        // $resultados = $query->setBindings([$fecha_inicio, $fecha_fin])->get();


        return $resultados;
    }

    //Tengo un error:SQLSTATE[08P01]: <<Unknown error>>: 7 ERROR: bind message supplies 2 parameters, but prepared statement "pdo_stmt_00000004" requires 5 (SQL: select "dv"."sucursal" as "nombre", "dv"."sucursal_marca_id", SUM(dv.importe_subtotal) AS importe_total_sum, "vc"."meta", SUM(dv.importe_subtotal) / vc.meta * 100 AS logro, (SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / SUM(dv.importe_subtotal) AS gm, SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion, "vc"."meta_contribucion" from "reporting_datamartventasclon" as "dv" inner join (SELECT vcc.sucursal, SUM(vcc.importe) AS meta, SUM(vcc.valor_contribucion) AS meta_contribucion FROM reporting_datamartventascuotaclon AS vcc WHERE vcc.fecha BETWEEN 2023-06-20 AND 2023-06-22 GROUP BY vcc.sucursal) AS vc on "dv"."sucursal" = "vc"."sucursal" where "dv"."fecha_documento" between ? and ? and "dv"."sucursal_marca_id" in (?) group by "dv"."sucursal", "dv"."sucursal_marca_id", "vc"."meta", "vc"."meta_contribucion" order by "importe_total_sum" asc)

    //esta es mi funcion
    public function getReporteVentaTiend($fecha_inicio, $fecha_fin, $tiendas_select)
    {



        $bi_conexion = DB::connection('pgsql2');

        $query = $bi_conexion->table('reporting_datamartventasclon AS dv')
            ->select(
                'dv.sucursal AS nombre',
                'dv.sucursal_marca_id',
                DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
                'vc.meta',
                DB::raw('SUM(dv.importe_subtotal) / vc.meta * 100 AS logro'),
                DB::raw('(SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / SUM(dv.importe_subtotal) AS gm'),
                DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
                'vc.meta_contribucion'
            )
            ->join(DB::raw('(SELECT vcc.sucursal, SUM(vcc.importe) AS meta, SUM(vcc.valor_contribucion) AS meta_contribucion
                    FROM reporting_datamartventascuotaclon AS vcc
                    WHERE vcc.fecha BETWEEN ? AND ?
                    GROUP BY vcc.sucursal) AS vc'), 'dv.sucursal', '=', 'vc.sucursal')
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            ->whereIn('dv.sucursal_marca_id', [$tiendas_select])
            ->groupBy('dv.sucursal', 'dv.sucursal_marca_id', 'vc.meta', 'vc.meta_contribucion')
            ->orderBy('importe_total_sum', 'ASC');

        $resultados = $query->setBindings([$fecha_inicio, $fecha_fin])->get();


        return $resultados;
    }

    public function getReporteVentaTiendaa($fecha_inicio, $fecha_fin, $tiendas_select)
    {
        $fecha_inicio = Carbon::parse($fecha_inicio)->format('Y-m-d');
        $fecha_fin = Carbon::parse($fecha_fin)->format('Y-m-d');

        $bi_conexion = DB::connection('pgsql2');

        $query = $bi_conexion->table('reporting_datamartventasclon AS dv')
            ->select(
                'dv.sucursal AS nombre',
                'dv.sucursal_marca_id',
                DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
                'vc.meta',
                DB::raw('SUM(dv.importe_subtotal) / vc.meta * 100 AS logro'),
                DB::raw('(SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / SUM(dv.importe_subtotal) AS gm'),
                DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
                'vc.meta_contribucion'
            )
            ->join(DB::raw('(SELECT vcc.sucursal, SUM(vcc.importe) AS meta, SUM(vcc.valor_contribucion) AS meta_contribucion
                FROM reporting_datamartventascuotaclon AS vcc
                WHERE vcc.fecha BETWEEN ? AND ?
                GROUP BY vcc.sucursal) AS vc'), 'dv.sucursal', '=', 'vc.sucursal')
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            ->whereIn('dv.sucursal_marca_id', [$tiendas_select])
            ->groupBy('dv.sucursal', 'dv.sucursal_marca_id', 'vc.meta', 'vc.meta_contribucion')
            ->orderBy('importe_total_sum', 'ASC');

        $resultados = $query->get([$fecha_inicio, $fecha_fin]);

        return $resultados;
    }



    public function getReporteVentaTienda($fecha_inicio, $fecha_fin, $tiendas_select)
    {

        var_dump($tiendas_select);
        $fecha_inicio =  "'" . date('Y-m-d') . "'";
        $fecha_fin = "'" . date('Y-m-d') . "'";

        $bi_conexion = DB::connection('pgsql2');

        $query = $bi_conexion->table('reporting_datamartventasclon AS dv')
            ->select(
                'dv.sucursal AS nombre',
                'dv.sucursal_marca_id',
                DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
                'vc.meta',
                DB::raw('SUM(dv.importe_subtotal) / vc.meta * 100 AS logro'),
                DB::raw('(SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / SUM(dv.importe_subtotal) AS gm'),
                DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
                'vc.meta_contribucion'
            )
            ->join(DB::raw('(SELECT vcc.sucursal, SUM(vcc.importe) AS meta, SUM(vcc.valor_contribucion) AS meta_contribucion
                    FROM reporting_datamartventascuotaclon AS vcc
                    WHERE vcc.fecha BETWEEN ? AND ?
                    GROUP BY vcc.sucursal) AS vc'), 'dv.sucursal', '=', 'vc.sucursal')
            //->whereRaw("dv.fecha_documento BETWEEN ".$fecha_inicio." AND $fecha_fin")
            ->whereIn('dv.sucursal_marca_id', $tiendas_select)
            ->groupBy('dv.sucursal', 'dv.sucursal_marca_id', 'vc.meta', 'vc.meta_contribucion')
            ->orderBy('importe_total_sum', 'ASC');

            $resultados = $query->setBindings([$fecha_inicio, $fecha_fin])->get();

        return $resultados;
    }






    public function getMetastienda($reporte)
    {

        $idsucursales = [];
        foreach ($reporte as $value) {
            $idsucursales = $value->nombre;
        }

        $bi_conexion = DB::connection('pgsql2');
        $resultados = $bi_conexion->table('reporting_datamartventascuotaclon')
            ->select(DB::raw('sum(importe) AS importe'), 'sucursal')
            ->whereIn('sucursal', $idsucursales)
            ->orderBy('importe', 'DESC')
            ->get();

        return $resultados;
    }

    public function getMarcas()
    {
        $bi_conexion = DB::connection('pgsql2');
        $marcas = $bi_conexion->table('reporting_marcas')->select('*')
            ->get();
        return $marcas;
    }

    public function getIDMarcas($marcas)
    {
        $marcas_select = [];
        foreach ($marcas as $value) {
            $marcas_select[] = $value->id;
        }
        return $marcas_select;
    }

    public function ValidarPermisos($id)
    {
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
     * @param  \App\Models\Report_enter  $report_enter
     * @return \Illuminate\Http\Response
     */
    public function show(Report_enter $report_enter)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Report_enter  $report_enter
     * @return \Illuminate\Http\Response
     */
    public function edit(Report_enter $report_enter)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Report_enter  $report_enter
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Report_enter $report_enter)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Report_enter  $report_enter
     * @return \Illuminate\Http\Response
     */
    public function destroy(Report_enter $report_enter)
    {
        //
    }
}
