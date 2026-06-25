<?php

namespace App\Http\Controllers;

use App\Models\Reporte_ventas;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

use Illuminate\Support\Facades\Queue;
use App\Jobs\ConsultaJob;
use Illuminate\Support\Facades\Cache;

class InicioController extends Controller
{
    public function index()
    {

        // La variable de sesión no existe
        $fecha_inicio =  date('Y-m-01');
        $fecha_fin = date('Y-m-t');

        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";

        $marcas = $this->getMarcas();

        $bi_conexion = DB::connection('pgsql');
        $results = $bi_conexion->table('datamart_ventas_actual as dv')
                ->selectRaw('SUM(dv.importe_subtotal) AS importe_total_sum, SUM(dv.cantidad) as unidades, SUM(dv.costo_venta) AS costo_venta, COUNT(DISTINCT dv.idtransaccion) as ticket')
                ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
                ->whereIn('dv.canal', ['E-COMMERCE', 'PUBLICO GENERAL', 'OUTLET','TIENDAS POR DEPARTAMENTO'])
                ->orderByDesc('importe_total_sum')
                ->get();
      
        $importeTotalSum = $results[0]->importe_total_sum;
        $unidades = $results[0]->unidades;
        $costoVenta = $results[0]->costo_venta;
        $ticket = $results[0]->ticket;

        //get monto del mes anterior:
        $hoy = strtotime('today');

        // Obtiene el primer día del mes anterior
        $primer_dia_mes_anterior = strtotime('first day of last month', $hoy);
        $fecha_inicio_mes_anterior = date('Y-m-d', $primer_dia_mes_anterior);
        $ultimo_dia_mes_anterior = strtotime('last day of last month', $hoy);
        $fecha_fin_mes_anterior = date('Y-m-d', $ultimo_dia_mes_anterior);
        $fecha_inicio = "'" . $fecha_inicio_mes_anterior . "'";
        $fecha_fin = "'" . $fecha_fin_mes_anterior . "'";
        //echo $ultimo_dia_mes_anterior;

        // $results_mes_anterior = Cache::remember('consulta_reporte_mes_anterior', 3600, function () use ($bi_conexion, $fecha_inicio, $fecha_fin, $marcas) {
        //     return $bi_conexion->table('datamart_ventas_actual as dv')
        //         ->selectRaw('SUM(dv.importe_subtotal) AS importe_total_sum, SUM(dv.cantidad) as unidades')
        //         ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
        //         //->whereIn('dv.sucursal_marca_id', $this->getIDMarcas($marcas))
        //         ->orderByDesc('importe_total_sum')
        //         ->get();

        //     //$querySql = $query->toSql();
        //     //echo $querySql;
        // });
        $results_mes_anterior = $bi_conexion->table('datamart_ventas_actual as dv')
            ->selectRaw('SUM(dv.importe_subtotal) AS importe_total_sum, SUM(dv.cantidad) as unidades')
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            //->whereIn('dv.sucursal_marca_id', $this->getIDMarcas($marcas))
            ->orderByDesc('importe_total_sum')
            ->get();

        $importeTotalSum_mes_aterior = $results_mes_anterior[0]->importe_total_sum;
        $unidades_mes_anterior = $results[0]->unidades;

        //echo $importeTotalSum_mes_aterior;

        $importes_actual = $this->importesAnioActual();
        $importes_anterior = $this->RepoimporteAnioAnterior();
        //var_dump($results);
        return view('inicio', compact('importeTotalSum', 'unidades', 'costoVenta', 'ticket', 'importes_actual', 'importeTotalSum_mes_aterior', 'unidades_mes_anterior', 'importes_anterior'));
    }

    public function importesAnioActual()
    {
        $fecha_inicio =  date('Y-01-01');
        $fecha_fin = date('Y-m-t');

        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";

        $marcas = $this->getMarcas();
        $bi_conexion = DB::connection('pgsql');

        $resultados = $bi_conexion->table('datamart_ventas_actual as dv')
            ->select(DB::raw("DATE_TRUNC('month', dv.fecha_documento) AS mes"))
            ->selectRaw("SUM(dv.importe_subtotal) AS importe_total_sum")
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            //->whereIn('dv.sucursal_marca_id', $this->getIDMarcas($marcas))
            ->groupBy(DB::raw("DATE_TRUNC('month', dv.fecha_documento)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', dv.fecha_documento)"))
            ->get()
            ->pluck('importe_total_sum') // Obtener un array plano de los valores de importe_total_sum
            ->toJson();

        //dd($resultados);

        return $resultados;
    }

    public function RepoimporteAnioAnterior()
    {
        // La variable de sesión no existe

        $fecha_inicio =  date('Y-01-01');
        $fecha_fin = date('Y-m-t');

        $fecha_inicio = date('Y-m-01', strtotime('-1 year', strtotime($fecha_inicio)));
        $fecha_fin = date('Y-m-t', strtotime('-1 year', strtotime($fecha_fin)));


        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";

        $marcas = $this->getMarcas();

        $bi_conexion = DB::connection('pgsql');
        $resultados =  $bi_conexion->table('datamart_ventas_2025 as dv')
            ->select(DB::raw("DATE_TRUNC('month', dv.fecha_documento) AS mes"))
            ->selectRaw("SUM(dv.importe_subtotal) AS importe_total_sum")
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            ->whereIn('dv.sucursal_marca_id', $this->getIDMarcas($marcas))
            ->groupBy(DB::raw("DATE_TRUNC('month', dv.fecha_documento)"))
            ->orderBy(DB::raw("DATE_TRUNC('month', dv.fecha_documento)"))
            ->get()
            ->pluck('importe_total_sum') // Obtener un array plano de los valores de importe_total_sum
            ->toJson();


        // $resultados =  $bi_conexion->table('datamart_ventas_2023 as dv')
        //     ->select(DB::raw("DATE_TRUNC('month', dv.fecha_documento) AS mes"))
        //     ->selectRaw("SUM(dv.importe_subtotal) AS importe_total_sum")
        //     ->whereBetween('dv.fecha_documento', [$fecha_inicio, '2023-06-30'])
        //     ->whereIn('dv.sucursal_marca_id', $this->getIDMarcas($marcas))
        //     ->groupBy(DB::raw("DATE_TRUNC('month', dv.fecha_documento)"))
        //     ->orderBy(DB::raw("DATE_TRUNC('month', dv.fecha_documento)"))
        //     ->toSql();
        // // ->pluck('importe_total_sum') // Obtener un array plano de los valores de importe_total_sum
        // // ->toJson();

        // dd($resultados);

        return $resultados;
    }

    public function cargarColas()
    {
        // Dividir la carga en dos hilos
        $primerResultado = null;
        $segundoResultado = null;
        $marcas = $this->getMarcas();
        // Ejecutar la primera mitad de la consulta en un hilo
        Queue::push(function () use ($marcas) {
            $bi_conexion = DB::connection('pgsql2');

            $primerResultado = $bi_conexion->table('reporting_datamartventasclon as dv')
                ->selectRaw('SUM(dv.importe_subtotal) AS importe_total_sum, SUM(dv.cantidad) as unidades, SUM(dv.costo_venta) AS costo_venta, COUNT(DISTINCT dv.idtransaccion) as ticket')
                ->whereBetween('dv.fecha_documento', ['2025-01-01', '2025-06-31'])
                ->whereIn('dv.sucursal_marca_id', [1, 3])
                ->orderByDesc('importe_total_sum')
                ->take(ceil($bi_conexion->table('reporting_datamartventasclon as dv')->whereBetween('dv.fecha_documento', ['2025-01-01', '2025-06-31'])->whereIn('dv.sucursal_marca_id', [1, 3])->count() / 2))
                ->get();

            // Hacer algo con el resultado de la primera mitad
            // Por ejemplo, asignarlo a una variable externa
            $GLOBALS['primerResultado'] = $primerResultado;
        });

        // Ejecutar la segunda mitad de la consulta en otro hilo
        Queue::push(function () use ($marcas) {
            $bi_conexion = DB::connection('pgsql2');

            $segundoResultado = $bi_conexion->table('reporting_datamartventasclon as dv')
                ->selectRaw('SUM(dv.importe_subtotal) AS importe_total_sum, SUM(dv.cantidad) as unidades, SUM(dv.costo_venta) AS costo_venta, COUNT(DISTINCT dv.idtransaccion) as ticket')
                ->whereBetween('dv.fecha_documento',  ['2023-07-01', '2023-07-29'])
                ->whereIn('dv.sucursal_marca_id',  [1, 3])
                ->orderByDesc('importe_total_sum')

                ->skip(ceil($bi_conexion->table('reporting_datamartventasclon as dv')->whereBetween('dv.fecha_documento', ['2023-07-01', '2023-07-29'])->whereIn('dv.sucursal_marca_id', [1, 3])->count() / 2))
                ->get();

            // Hacer algo con el resultado de la segunda mitad
            // Por ejemplo, asignarlo a una variable externa
            $GLOBALS['segundoResultado'] = $segundoResultado;
        });

        // Esperar a que se completen los trabajos en la cola
        while (!isset($primerResultado) || !isset($segundoResultado)) {
            usleep(10000); // Esperar 10 milisegundos
        }

        // Combinar los resultados de los dos hilos
        $resultados = $primerResultado->concat($segundoResultado);
        var_dump($resultados);


        /******************************** */
    }

    public function getMarcas()
    {
        $bi_conexion = DB::connection('pgsql');
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
}
