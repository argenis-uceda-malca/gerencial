<?php

namespace App\Http\Controllers;


use App\Models\Report_enter;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Log\Logger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ui\Presets\React;
use PhpParser\Node\Expr\FuncCall;


class ReportRfmController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */


    public function rfm()
    {
        //dd($_SERVER['REMOTE_ADDR']);
        $fecha_inicio =  date('Y-m-d');
        $fecha_fin = date('Y-m-d');

        $sucursales = $this->getSucursales();

        //$reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false);
        //$datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);
        //$reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false, "get-reporte-marcas");
        //$reporte = collect(json_decode(json_encode($reporte_api)));
        //dd($reporte_api);
        //var_dump($reporte);
        //var_dump($prueba);
        // $anio_anterior = $this->getImporteAnioAterior($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas));

        //$idmarcas = $this->getIDMarcas($marcas);
        //var_dump($fecha_inicio);
        $option = 'marca';
        $fecha = $fecha_inicio . ' al ' . $fecha_fin;
        $titulo = "Detalle por Marcas";
        $filtroonline = 1;
        $filtroincluironline = 0;
        $filtrotxd = 0;

        // $entradas  = $this->getLocatioIdTiendas($fecha_inicio, $fecha_fin, [297, 4]);
        $sucursal = '';


        // dd($respeusta);
        //var_dump($entradas);
        //var_dump($prueba);
        return view('gestion_rfm', compact('sucursales'));
    }


    public function submit_rfm(Request $request)
    {

    
        $datos = $this->getDatosApi('get-reporte-rfm',$request);
        // Log::info(json_encode($datos, JSON_PRETTY_PRINT)); // Solo para registrar bonito en el log

        return response()->json($datos); // Esto es lo que debe ir al cliente
    }


    public function getDatosApi($type,$request)
    {
        $token = rest_api_token();
        if (!in_array('Bearer', $token)) {
            return response()->json(['iserror' => "Ocurrio un error en la autenticacion."]);
        }

        $http_header = [
            "Authorization: {$token['token_type']} {$token['access_token']}",
            "Content-Type: application/json",
        ];
        //dd($http_header);
        //dd($canal1, $canal2, $canal3);
        //dd($marcas_select);
        Log::info(json_encode($request->sucursales, JSON_PRETTY_PRINT)); // Solo para registrar bonito en el log

        $solicitud = [
            "type" => $type,
            "sucursales" => $request->sucursales,
            "fecha_inicio" => $request->fecha_inicio,
            "fecha_fin" => $request->fecha_fin,
            // "marcas_select" => $marcas_select,
            // "canal1" => $canal1,
            // "canal2" => $canal2,
            // "canal3" => $canal3,
            //"marca_id" => "12",
        ];
        //$prueba = prueba("esto es una preuba");
        //dd($prueba);
        $rpta = json_decode(rest_api($solicitud, get_url_api_rest_reporte_rfm(), "POST", 300, $http_header), true);
        //dd(rest_api($solicitud, get_url_api_rest_reporte(), "POST", 300, $http_header));
        //dd($rpta);
        return $rpta;
    }

    // public function getSucursales()
    // {
    //     $bi_conexion = DB::connection('pgsql');
    //     $marcas = $bi_conexion->table('dm_comercial_sucursales')->select('*')
    //         ->get();
    //     return $marcas;
    // }

    public function getSucursales()
    {
        $bi_conexion = DB::connection('pgsql');

        $sucursales = $bi_conexion->table('dm_comercial_sucursales as dc')
            ->distinct()
            ->select('dc.idsucursal', 'dc.abreviado')
            ->leftJoin('datamart_ventas_actual as dv', 'dv.sucursal', '=', 'dc.sucursal')
            ->where('dc.abreviado', 'not like', '%OFICINA%')
            ->where('dv.fecha_documento', '>=', DB::raw("CURRENT_DATE - INTERVAL '7 days'"))
            ->get();

        return $sucursales;
    }
}
