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

class ReportEnterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //Ya no se hace llamados a este index
        $fecha_inicio =  date('Y-m-d');
        $fecha_fin = date('Y-m-d');

        $marcas = $this->getMarcas();


        $reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false);
        $datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);



        //$idmarcas = $this->getIDMarcas($marcas);
        //var_dump($fecha_inicio);
        $option = 'marca';
        $fecha = $fecha_inicio . ' al ' . $fecha_fin;
        $titulo = "Detalle por Marcas";

        return view('reporte_venta', compact('datos', 'marcas', 'reporte', 'option', 'fecha', 'titulo'));
    }

    public function reportesb()
    {
        //dd($_SERVER['REMOTE_ADDR']);
        $fecha_inicio =  date('Y-m-d');
        $fecha_fin = date('Y-m-d');

        $marcas = $this->getMarcas();

         $canal1= true;
        $canal2= false;
        $canal3= false;

        $reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false);
        //$datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);
        //$reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false, "get-reporte-marcas"); ///comentado 
        //$reporte = collect(json_decode(json_encode($reporte_api))); ///comentado 
        //dd($reporte_api);
        //var_dump($reporte);
        //var_dump($prueba);
        $anio_anterior = $this->getImporteAnioAterior($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), $canal1, $canal2, $canal3);

        //$idmarcas = $this->getIDMarcas($marcas);
        //var_dump($fecha_inicio);
        $option = 'marca';
        $fecha = $fecha_inicio . ' al ' . $fecha_fin;
        $titulo = "Detalle por Marcas";
        $filtroonline = 1;
        $filtroincluironline = 0;
        $filtrotxd = 0;

        //var_dump($anio_anterior);
        $entradas  = $this->getLocatioIdTiendas($fecha_inicio, $fecha_fin, [297, 4]);
        //var_dump($entradas);
        //var_dump($prueba);
        return view('reportesb', compact('marcas', 'reporte', 'option', 'fecha', 'titulo', 'anio_anterior', 'entradas', 'filtroonline', 'filtroincluironline', 'filtrotxd'));
    }

    public function submitForm(Request $request)
    {

        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $fecha_inicio = $request->input('fecha_inicio');
            $fecha_fin = $request->input('fecha_fin');

            $filtroonline = $request->input('filtroonline');
            $filtroincluironline = $request->input('filtroincluironline');
            $filtrotxd = $request->input('filtrotxd');

            $marcas_seleccionadas = array();
            $marcas_seleccionadas = $request->input('marca', []);
            //var_dump($marcas_seleccionadas);
        }
        $marcas = $this->getMarcas();
        $canal1= true;
        $canal2= false;
        $canal3= false;

        if ($request->filled('select_radio') && $request->input('select_radio') == 1) {
            /*Marcas */
            //var_dump($filtroincluironline);

            $reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $marcas_seleccionadas, $filtroonline, $filtroincluironline, $filtrotxd); //consulta directa al smart

            /**Usando la api */
            $reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $marcas_seleccionadas, $filtroonline, $filtroincluironline, $filtrotxd, "get-reporte-marcas");
            $reporte = collect(json_decode(json_encode($reporte_api)));
            dd($reporte);
            /**End Usando la api */

            $idsMarcas = $reporte->pluck('id')->toArray();

            $option = 'marca';
            $titulo = "Detalle por Marcas";
            $anio_anterior = $this->getImporteAnioAterior($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), $canal1, $canal2, $canal3);
            $entradas = $this->getLocatioIdMarcas($fecha_inicio, $fecha_fin, $marcas_seleccionadas);
            //var_dump($entradas);
            //var_dump($idsMarcas);
        } else {
            /**Tiendas */
            //dd($marcas_seleccionadas);
            $reporte = $this->getReporteVentaTienda($fecha_inicio, $fecha_fin, $marcas_seleccionadas, $filtroonline, $filtroincluironline, $filtrotxd); //consulta directa al smart

            /**Usando la api */
            //$reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $marcas_seleccionadas, $filtroonline, $filtroincluironline, $filtrotxd, "get-reporte-tiendas");
            //$reporte = collect(json_decode(json_encode($reporte_api)));
            /**End Usando la api */

            //dd($reporte);
            $idsTiendas = $reporte->pluck('id')->toArray();

            $option = 'tienda';
            $titulo = "Detalle por Tiendas";
            //$metas = $this->getMetastienda($reporte);

            $anio_anterior = $this->getImporteAnioAnteriorTiendas($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), $filtroonline, $filtroincluironline, $filtrotxd);
            $entradas  = $this->getLocatioIdTiendas($fecha_inicio, $fecha_fin, $idsTiendas);
        }

        //$datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);
        $fecha = $fecha_inicio . ' al ' . $fecha_fin;



        //Vista simplificada
        return view('reportesb', compact('marcas', 'reporte', 'option', 'fecha', 'titulo', 'anio_anterior', 'entradas', 'filtroonline', 'filtroincluironline', 'filtrotxd'));

        //Vista con todos los campos completos
        //return view('reporte_venta', compact('datos', 'marcas', 'reporte', 'option', 'fecha', 'titulo'));
    }

    public function vista_tabla()
    {
        //dd($_SERVER['REMOTE_ADDR']);
        $fecha_inicio =  date('Y-m-d');
        $fecha_fin = date('Y-m-d');

        $marcas = $this->getMarcas();

        $sucursales = DB::table('datamart_ventas_actual')
            ->select('sucursal')
            ->distinct()
            ->orderBy('sucursal')
            ->get();

        $sucursalesActivas = DB::table('config_sucursales')
            ->pluck('sucursal')
            ->toArray();

        $canal1= true;
        $canal2= false;
        $canal3= false;

        //$reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false);
        //$datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);
        //$reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false, "get-reporte-marcas");
        //$reporte = collect(json_decode(json_encode($reporte_api)));
        //dd($reporte_api);
        //var_dump($reporte);
        //var_dump($prueba);
        $anio_anterior = $this->getImporteAnioAterior($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), $canal1, $canal2, $canal3);

        //$idmarcas = $this->getIDMarcas($marcas);
        //var_dump($fecha_inicio);
        $option = 'marca';
        $fecha = $fecha_inicio . ' al ' . $fecha_fin;
        $titulo = "Detalle por Marcas";
        $filtroonline = 1;
        $filtroincluironline = 0;
        $filtrotxd = 0;

        $entradas  = $this->getLocatioIdTiendas($fecha_inicio, $fecha_fin, [297, 4]);
        //var_dump($entradas);
        //var_dump($prueba);
        return view('nueva_tabla', compact('marcas', 'option', 'fecha', 'titulo', 'anio_anterior', 'entradas', 'filtroonline', 'filtroincluironline', 'filtrotxd','sucursales','sucursalesActivas'));
    }

    public function nueva_tabla(Request $request)
    {

        Log::debug("Click en nueva tabla"); 
        if ($request->filled('fecha_inicio') && $request->filled('fecha_fin')) {
            $fecha_inicio = $request->input('fecha_inicio');
            $fecha_fin = $request->input('fecha_fin');

            // $filtroonline = $request->input('filtroonline');
            // $filtroincluironline = $request->input('filtroincluironline');
            // $filtrotxd = $request->input('filtrotxd');

            $filtroonline = $request->input('filtroonline') ?? false;
            $filtroincluironline = $request->input('filtroincluironline') ?? false;
            $filtrotxd = $request->input('filtrotxd') ?? false;
            /**Si es null setearlo a false */

            $marcas_seleccionadas = array();
            $marcas_seleccionadas = $request->input('marca', []);
            //var_dump($marcas_seleccionadas);
        }
        $marcas = $this->getMarcas();

        if ($request->filled('select_radio') && $request->input('select_radio') == 1) {
            /*Marcas */
            //var_dump($filtroincluironline);

            //Log::info('Consulta reporte marcas:');
            $reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $marcas_seleccionadas, $filtroonline, $filtroincluironline, $filtrotxd); //consulta directa al smart
            Log::info('Consulta reporte marcas:' . $reporte);
            $idsMarcas = $reporte->pluck('id')->toArray();

            Log::debug('Filtro Online: ' . $filtroonline);
            Log::debug('Filtro Incluir Online: ' . $filtroincluironline);

            $option = 'marca';
            $titulo = "Detalle por Marcas";
            $anio_anterior = $this->getImporteAnioAterior($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas),$filtroonline, $filtroincluironline, $filtrotxd);
            if (!$filtroonline && $filtroincluironline) {
                $entradas = [];
            } else {
                $entradas = $this->getLocatioIdMarcas($fecha_inicio, $fecha_fin, $marcas_seleccionadas);
            }

            //var_dump(json_encode($anio_anterior));
            //var_dump(json_encode($reporte));

            //var_dump(json_encode($reporte));

            $resultadosFusionados = array(); // Array para almacenar las fusiones

            foreach ($reporte as $item) {
                $entradaCoincidente = false; // Bandera para controlar si se encontró una entrada coincidente en $entradas
                $entradaCoincidente2 = false; // Bandera para controlar si se encontró una entrada coincidente en $anio_anterior

                // Fusionar con los datos de $entradas si hay coincidencia
                foreach ($entradas as $entrada) {
                    if ($item->id == $entrada->id) {
                        $item->total = $entrada->total; // Fusionar el dato "total" del $entrada en el $item
                        $entradaCoincidente = true;
                        break;
                    }
                }

                // Fusionar con los datos de $anio_anterior si hay coincidencia
                foreach ($anio_anterior as $anterior) {
                    if ($item->id == $anterior->id) {
                        $item->anterior = $anterior->anterior; // Fusionar el dato "total" del $anio_anterior en el $item
                        $entradaCoincidente2 = true;
                        break;
                    }
                }

                // Si no hay coincidencia con $entradas, establecer "total" en cero
                if (!$entradaCoincidente) {
                    $item->total = 0;
                }

                // Si no hay coincidencia con $anio_anterior, establecer "anterior" en cero
                if (!$entradaCoincidente2) {
                    $item->anterior = 0;
                }

                // Agregar el item al array de resultados fusionados
                $resultadosFusionados[] = $item;
            }

            // Convertir el array de resultados fusionados en un JSON único
            $jsonFinal = json_encode($resultadosFusionados, JSON_PRETTY_PRINT);
        } else {
            $resultadosFusionados = array();
            /**Tiendas */
            //dd($marcas_seleccionadas);
            $reporte = $this->getReporteVentaTienda($fecha_inicio, $fecha_fin, $marcas_seleccionadas, $filtroonline, $filtroincluironline, $filtrotxd); //consulta directa al smart


            //dd($reporte);
            $idsTiendas = $reporte->pluck('id')->toArray();

            $option = 'tienda';
            $titulo = "Detalle por Tiendas";
            //$metas = $this->getMetastienda($reporte);

            $anio_anterior = $this->getImporteAnioAnteriorTiendas($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), $filtroonline, $filtroincluironline, $filtrotxd);
            $entradas  = $this->getLocatioIdTiendas($fecha_inicio, $fecha_fin, $idsTiendas);
            //dd($entradas);
            Log::info("COTTROL resultadosFusionados");

            foreach ($reporte as $item) {
                $entradaCoincidente = false; // Bandera para controlar si se encontró una entrada coincidente en $entradas
                $entradaCoincidente2 = false; // Bandera para controlar si se encontró una entrada coincidente en $anio_anterior

                // Fusionar con los datos de $entradas si hay coincidencia
                /**HAY QUE VALIDAR SI ESQUE NO HAY ENTRADAS SE ´PUEDE CAER AQUI */
                foreach ($entradas as $entrada) {
                    if ($item->id == $entrada->id) {
                        $item->total = $entrada->total; // Fusionar el dato "total" del $entrada en el $item
                        $entradaCoincidente = true;
                        break;
                    }
                }

                // Fusionar con los datos de $anio_anterior si hay coincidencia
                foreach ($anio_anterior as $anterior) {
                    if ($item->id == $anterior->id) {
                        $item->anterior = $anterior->anterior; // Fusionar el dato "total" del $anio_anterior en el $item
                        $entradaCoincidente2 = true;
                        break;
                    }
                }

                // Si no hay coincidencia con $entradas, establecer "total" en cero
                if (!$entradaCoincidente) {
                    $item->total = 0;
                }

                // Si no hay coincidencia con $anio_anterior, establecer "anterior" en cero
                if (!$entradaCoincidente2) {
                    $item->anterior = 0;
                }

                // Agregar el item al array de resultados fusionados
                $resultadosFusionados[] = $item;
                Log::info("COTTROL resultadosFusionados22");
            }

            // Convertir el array de resultados fusionados en un JSON único
            $jsonFinal = json_encode($resultadosFusionados, JSON_PRETTY_PRINT);
            Log::info($jsonFinal);
        }

        echo $jsonFinal;


        //return view('nueva_tabla', compact('marcas', 'reporte', 'option', 'fecha', 'titulo', 'anio_anterior', 'entradas', 'filtroonline', 'filtroincluironline', 'filtrotxd'));
    }

    public function vermas_tienda(Request $request)
    {

        $fecha_inicio = $request->query('fecha_inicio');
        $fecha_fin = $request->query('fecha_fin');
        $sucursalId = $request->query('id');
        $opcion = $request->query('tipo');

        $filtroonline = $request->input('filtroonline');
        $filtroincluironline = $request->input('filtroincluironline');
        $filtrotxd = $request->input('filtrotxd');

        // $fecha_inicio =  "'" . $fecha_inicio . "'";
        // $fecha_fin = "'" . $fecha_fin . "'";

        $marcas = $this->getMarcas();

        if ($opcion == 'tienda') {
            $reporte = $this->get_vermas_tienda($fecha_inicio, $fecha_fin, $sucursalId); //funcion funcionando antes de la api
            //$reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $sucursalId, $filtroonline, $filtroincluironline, $filtrotxd, "get-reporte-tiendas_vermas");
            //$reporte = collect(json_decode(json_encode($reporte_api)));
            dd($reporte);
            $fecha = $fecha_inicio . ' al ' . $fecha_fin;
            return view('ver_mas', compact('marcas', 'reporte', 'fecha'));
        } else {
            //echo $opcion;
            //$datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);

            $sucursalId = [$request->query('id')];
            $reporte = $this->getReporteVentaTienda($fecha_inicio, $fecha_fin, $sucursalId, $filtroonline, $filtroincluironline, $filtrotxd); //consulta directa al smart
            //$reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $sucursalId, $filtroonline, $filtroincluironline, $filtrotxd, "get-reporte-tiendas");
            //$reporte = collect(json_decode(json_encode($reporte_api)));

            $idsTiendas = $reporte->pluck('id')->toArray();

            $option = 'tienda';
            //var_dump($reporte);
            $fecha = $fecha_inicio . ' al ' . $fecha_fin;
            $titulo = "Detalle por Tiendas";

            $anio_anterior = $this->getImporteAnioAnteriorTiendas($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), $filtroonline, $filtroincluironline, $filtrotxd);

            $entradas  = $this->getLocatioIdTiendas($fecha_inicio, $fecha_fin, $idsTiendas);

            foreach ($reporte as $item) {
                $entradaCoincidente = false; // Bandera para controlar si se encontró una entrada coincidente en $entradas
                $entradaCoincidente2 = false; // Bandera para controlar si se encontró una entrada coincidente en $anio_anterior

                // Fusionar con los datos de $entradas si hay coincidencia
                foreach ($entradas as $entrada) {
                    if ($item->id == $entrada->id) {
                        $item->total = $entrada->total; // Fusionar el dato "total" del $entrada en el $item
                        $entradaCoincidente = true;
                        break;
                    }
                }

                // Fusionar con los datos de $anio_anterior si hay coincidencia
                foreach ($anio_anterior as $anterior) {
                    if ($item->id == $anterior->id) {
                        $item->anterior = $anterior->anterior; // Fusionar el dato "total" del $anio_anterior en el $item
                        $entradaCoincidente2 = true;
                        break;
                    }
                }

                // Si no hay coincidencia con $entradas, establecer "total" en cero
                if (!$entradaCoincidente) {
                    $item->total = 0;
                }

                // Si no hay coincidencia con $anio_anterior, establecer "anterior" en cero
                if (!$entradaCoincidente2) {
                    $item->anterior = 0;
                }

                // Agregar el item al array de resultados fusionados
                $resultadosFusionados[] = $item;
            }

            // Convertir el array de resultados fusionados en un JSON único
            $jsonFinal = json_encode($resultadosFusionados, JSON_PRETTY_PRINT);
            //echo $jsonFinal;
            //Vista simplificada
            return view('reportesb', compact('marcas', 'reporte', 'option', 'fecha', 'titulo', 'anio_anterior', 'entradas', 'filtroonline', 'filtroincluironline', 'filtrotxd'));

            //Vista con todos los campos completos
            //return view('reporte_venta', compact('datos', 'marcas', 'reporte', 'option', 'fecha', 'titulo'));
        }


        //var_dump($reporte);

    }

    public function vermas_tienda2(Request $request)
    {

        $fecha_inicio = $request->input('fecha_inicio');
        $fecha_fin = $request->input('fecha_fin');
        $sucursalId = $request->input('id');
        //$opcion = $request->query('tipo');

        //$marcas = $this->getMarcas();


        $reporte = $this->get_vermas_tienda($fecha_inicio, $fecha_fin, $sucursalId); //funcion funcionando antes de la api

        $fecha = $fecha_inicio . ' al ' . $fecha_fin;
        //return view('ver_mas', compact('marcas', 'reporte', 'fecha'));
        $arrayReporte = $reporte->toArray();
        //dd($reporte);
        return response()->json($arrayReporte);
    }

    public function get_vermas_tienda($fecha_inicio, $fecha_fin, $sucursalId)
    {
        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";

        // $bi_conexion = DB::connection('pgsql');
        // $resultados = $bi_conexion->table('reporting_datamartventasclon as dv')
        //     ->leftJoin('vista_datamart_productos as vdp', 'vdp.codigo_producto', '=', 'dv.codigo_producto')
        //     ->select('dv.sucursal', 'dv.idsucursal', 'vdp.codigo_producto', 'vdp.codigo_padre', 'vdp.producto', 'dv.cantidad', 'dv.costo_unitario', 'dv.importe_subtotal', 'dv.costo_venta', 'dv.fecha_documento', 'dv.persona')
        //     ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
        //     ->whereIn('dv.idsucursal', [$sucursalId])
        //     ->get();
        $case  = strval('case  
        when dv.codigo_documento=\'001\' then \'FT \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
        when dv.codigo_documento=\'002\' then \'BV \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
        when dv.codigo_documento=\'003\' then \'NC \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
        when dv.codigo_documento=\'067\' then \'FE \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
        when dv.codigo_documento=\'068\' then \'BE \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
        when dv.codigo_documento=\'069\' then \'NE \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
        end as comprobante');

        $case = "'" . $case . "'";

        $bi_conexion = DB::connection('pgsql');
        $resultados = $bi_conexion->table('datamart_ventas_actual as dv')
            ->leftJoin('datamart_logistica_productos as vdp', 'vdp.codigo_producto', '=', 'dv.codigo_producto')
            ->leftJoin('datamart_logistica_productos as b', 'dv.codigo_producto', "=", 'b.codigo_producto')
            ->select(
                'dv.sucursal',
                'dv.idsucursal',
                'vdp.codigo_producto',
                'vdp.codigo_padre',
                'vdp.producto',
                'dv.cantidad',
                DB::raw('dv.importe_subtotal / dv.cantidad as costo_unitario'),
                'dv.importe_subtotal',
                DB::raw('(
                case b.iditem_obsolescencia
                    when \'OBSOLETO\' THEN (
                                            CASE WHEN ( select  pc3.fecha from datamart_logistica_movimientos_actual  pc3  where pc3.IDMOVIMIENTO = 57 and pc3.CODIGO_PRODUCTO  = dv.CODIGO_PRODUCTO order by FECHA limit 1) <= dv.fecha_documento then 0
                                            ELSE (
                                                CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                                                ELSE (
                                                    case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                                    ELSE (
                                                         COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                                         )
                                                    END )
                                                END )
                                            END )
                ELSE (
                    CASE dv.FLAG_NOTACREDITO WHEN \'S\' THEN (
                        CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                        ELSE (
                            case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                            ELSE (
                                 COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                )
                            END )
                        END ) * 1
                    ELSE (
                        CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                        ELSE (
                            case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                            ELSE (
                                 COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                )
                            END )
                        END )
                    END )
                END ) as costo_venta'),
                'dv.fecha_documento',
                'dv.persona',
                DB::raw('case  
            when dv.codigo_documento=\'001\' then \'FT \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
            when dv.codigo_documento=\'002\' then \'BV \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
            when dv.codigo_documento=\'003\' then \'NC \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
            when dv.codigo_documento=\'067\' then \'FE \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
            when dv.codigo_documento=\'068\' then \'BE \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
            when dv.codigo_documento=\'069\' then \'NE \' || to_char(dv.numero_serie,\'000\')||\'-\'|| to_char(dv.numero_documento,\'000000\')
            end as comprobante')
            )
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            ->whereIn('dv.idsucursal', [$sucursalId])
            ->get();



        return $resultados;
    }


    public function getCosto()
    {
        $bi_conexion = DB::connection('pgsql');
        // $resultados = $bi_conexion->table('datamart_ventas')
        // ->select('a.idtransaccion', 'a.codigo_producto', 'a.cantidad',
        // DB::raw;
        // )
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

    public function getImporteAnioAterior_original($fecha_inicio, $fecha_fin, $marcas_select, $canal1, $canal2, $canal3)
    {


        $fechaActual_inicio = Carbon::parse($fecha_inicio);
        $fechaActual_fin = Carbon::parse($fecha_fin);
        
        // $fecha_inicio = $this->getfechaequivalente($fechaActual_inicio);
        // $fecha_fin = $this->getfechaequivalente($fechaActual_fin);
        $fecha_inicio = $fechaActual_inicio;
        $fecha_fin = $fechaActual_fin;

        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";

        $sucursalesActivas = DB::table('config_sucursales')
        ->pluck('sucursal')
        ->toArray();

        set_time_limit(300);
        DB::enableQueryLog();

        $bi_conexion = DB::connection('pgsql');
        $resultados = $bi_conexion->table('datamart_ventas_2025 as dv')
            ->select(
                'dv.sucursal_marca_id AS id',
                DB::raw('SUM(dv.importe_subtotal) AS anterior')
            )
            ->leftJoin('pla_fechas_equivalentes as f', DB::raw('COALESCE(dv.fecha_documento, dv.fecha_documento)'), '=', 'f.fecha')
            ->leftJoin('datamart_calendario as c', DB::raw('COALESCE(f.fecha_equivalente, dv.fecha_documento)'), '=', 'c.fecha')
            // ->leftJoin('datamart_logistica_productos as b', 'dv.codigo_producto', '=', 'b.codigo_producto')  // agregado
            ->leftJoin('datamart_logistica_productos as b', function ($join) {
                $join->on('dv.codigo_producto', '=', 'b.codigo_producto')
                    ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']);
            })
            // ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']) // agregado
            ->where('dv.sucursal', '<>', 'SEDE CENTRAL') // agregado
            ->whereIn('dv.sucursal', $sucursalesActivas)

            // ->whereBetween('f.fecha', [$fecha_inicio, $fecha_fin]);
            ->whereIn('f.fecha', function($query) use ($fecha_inicio, $fecha_fin) {
                $query->select('fecha')
                    ->from('pla_fechas_equivalentes')
                    ->whereBetween('fecha_equivalente', [$fecha_inicio, $fecha_fin]);
            });


            if ($canal1 == true && $canal2 == false && $canal3 == false) {
                $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
                $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == false && $canal2 == true && $canal3 == false) {
                $resultados = $resultados->where('canal',  'E-COMMERCE');
            }
            if ($canal1 == false && $canal2 == false && $canal3 == true) {
                $resultados = $resultados->where('canal', '==', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == true && $canal2 == true && $canal3 == false) {
                $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == false && $canal2 == true && $canal3 == true) {
                $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
            }

            $resultados = $resultados->whereIn('dv.sucursal_marca_id', $marcas_select)
            ->groupBy('dv.sucursal_marca_id')
            ->get();
        $consulta_sql = DB::getQueryLog();
        // Registra la consulta SQL en el log
        Log::info('Consulta SQL ventas_anterior:', $consulta_sql);
        return $resultados;
    }


    public function getImporteAnioAterior($fecha_inicio, $fecha_fin, $marcas_select, $canal1, $canal2, $canal3)
    {
        $fechaActual_inicio = Carbon::parse($fecha_inicio);
        $fechaActual_fin = Carbon::parse($fecha_fin);
        
        // $fecha_inicio = $this->getfechaequivalente($fechaActual_inicio);
        // $fecha_fin = $this->getfechaequivalente($fechaActual_fin);
        $fecha_inicio = $fechaActual_inicio;
        $fecha_fin = $fechaActual_fin;
        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";
        $sucursalesActivas = DB::table('config_sucursales')
        ->pluck('sucursal')
        ->toArray();
        set_time_limit(300);
        DB::enableQueryLog();
        $bi_conexion = DB::connection('pgsql');
        $resultados = $bi_conexion->table('datamart_ventas_2025 as dv')
            ->select(
                'dv.sucursal_marca_id AS id',
                DB::raw('SUM(dv.importe_subtotal) AS anterior')
            )
            ->leftJoin('pla_fechas_equivalentes as f', DB::raw('COALESCE(dv.fecha_documento, dv.fecha_documento)'), '=', 'f.fecha')
            ->leftJoin('datamart_calendario as c', DB::raw('COALESCE(f.fecha_equivalente, dv.fecha_documento)'), '=', 'c.fecha')
            // ->leftJoin('datamart_logistica_productos as b', 'dv.codigo_producto', '=', 'b.codigo_producto')  // agregado
            ->leftJoin('datamart_logistica_productos as b', function ($join) {
                $join->on('dv.codigo_producto', '=', 'b.codigo_producto')
                    ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']);
            })
            // ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']) // agregado
            ->where('dv.sucursal', '<>', 'SEDE CENTRAL') // agregado
            ->whereIn('dv.sucursal', $sucursalesActivas)
            // ->whereBetween('f.fecha', [$fecha_inicio, $fecha_fin]);
            ->whereIn('f.fecha', function($query) use ($fecha_inicio, $fecha_fin) {
                $query->select('fecha')
                    ->from('pla_fechas_equivalentes')
                    ->whereBetween('fecha_equivalente', [$fecha_inicio, $fecha_fin]);
            });
            if ($canal1 == true && $canal2 == false && $canal3 == false) {
                $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
                $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == false && $canal2 == true && $canal3 == false) {
                $resultados = $resultados->where('canal',  'E-COMMERCE');
            }
            if ($canal1 == false && $canal2 == false && $canal3 == true) {
                $resultados = $resultados->where('canal', '==', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == true && $canal2 == true && $canal3 == false) {
                // ANTES: where('canal', '!=', 'TIENDAS POR DEPARTAMENTO')
                // Esto mezclaba boutiques + online en la misma fila por marca.
                // Ahora: igual que "canal1 solo", para que esta query traiga
                // SOLO boutiques. El "anterior" de la fila WEB se agrega
                // aparte, más abajo, igual que en getReporteMarcaVenta.
                $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
                $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == false && $canal2 == true && $canal3 == true) {
                $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
            }
            $resultados = $resultados->whereIn('dv.sucursal_marca_id', $marcas_select)
            ->groupBy('dv.sucursal_marca_id')
            ->get();
        $consulta_sql = DB::getQueryLog();
        // Registra la consulta SQL en el log
        Log::info('Consulta SQL ventas_anterior:', $consulta_sql);

        // ============================================================
        // BLOQUE NUEVO: cuando se seleccionan Boutiques (canal1) y
        // Online (canal2) al mismo tiempo, se agrega una fila extra
        // con id='WEB' y el 'anterior' total de E-COMMERCE (todas las
        // marcas sumadas), para que el merge por id en nueva_tabla()
        // encuentre el histórico correspondiente a la fila "WEB" que
        // genera getReporteMarcaVenta().
        //
        // Es la MISMA query que "canal2 solo", pero sin agrupar por
        // marca, para que SUM() agregue sobre el total y Postgres
        // devuelva una única fila ya calculada.
        // ============================================================
        if ($canal1 == true && $canal2 == true && $canal3 == false) {

            $filaWebAnterior = $bi_conexion->table('datamart_ventas_2025 as dv')
                ->select(
                    DB::raw("'WEB' AS id"),
                    DB::raw('SUM(dv.importe_subtotal) AS anterior')
                )
                ->leftJoin('pla_fechas_equivalentes as f', DB::raw('COALESCE(dv.fecha_documento, dv.fecha_documento)'), '=', 'f.fecha')
                ->leftJoin('datamart_calendario as c', DB::raw('COALESCE(f.fecha_equivalente, dv.fecha_documento)'), '=', 'c.fecha')
                ->leftJoin('datamart_logistica_productos as b', function ($join) {
                    $join->on('dv.codigo_producto', '=', 'b.codigo_producto')
                        ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']);
                })
                ->where('dv.sucursal', '<>', 'SEDE CENTRAL')
                ->whereIn('dv.sucursal', $sucursalesActivas)
                ->whereIn('f.fecha', function($query) use ($fecha_inicio, $fecha_fin) {
                    $query->select('fecha')
                        ->from('pla_fechas_equivalentes')
                        ->whereBetween('fecha_equivalente', [$fecha_inicio, $fecha_fin]);
                })
                ->where('canal', 'E-COMMERCE')
                ->whereIn('dv.sucursal_marca_id', $marcas_select)
                ->first();
            // Nota: sin groupBy aquí a propósito, para que todo el
            // resultado E-COMMERCE colapse en una sola fila.

            // Importante: SUM() sobre cero filas (sin ventas E-COMMERCE
            // en el periodo equivalente del año anterior) devuelve NULL,
            // no 0. Si se descartara la fila en ese caso, nueva_tabla()
            // nunca encontraría el id='WEB' al hacer el merge, y la fila
            // WEB del reporte actual quedaría siempre con anterior=0
            // (sin distinguir "no hay dato" de "hubo un error"). Por eso
            // se normaliza a 0 aquí y siempre se agrega la fila.
            if ($filaWebAnterior === null) {
                $filaWebAnterior = new \stdClass();
                $filaWebAnterior->id = 'WEB';
                $filaWebAnterior->anterior = 0;
            } elseif ($filaWebAnterior->anterior === null) {
                $filaWebAnterior->anterior = 0;
            }

            $resultados->push($filaWebAnterior);

            $consulta_sql = DB::getQueryLog();
            Log::info('Consulta SQL ventas_anterior (con fila WEB):', $consulta_sql);
        }

        return $resultados;
    }

    public function getImporteAnioAnteriorTiendas($fecha_inicio, $fecha_fin, $marcas_select, $canal1, $canal2, $canal3)
    {
        $fechaActual_inicio = Carbon::parse($fecha_inicio);
        $fechaActual_fin = Carbon::parse($fecha_fin);
        // $fecha_inicio = $fechaActual_inicio->subYear();
        // $fecha_fin = $fechaActual_fin->subYear();
        // $fecha_inicio = $this->getfechaequivalente($fechaActual_inicio);
        // $fecha_fin = $this->getfechaequivalente($fechaActual_fin);

        $fecha_inicio =  "'" . $fechaActual_inicio . "'";
        $fecha_fin = "'" . $fechaActual_fin . "'";

        //echo "hola";
        DB::enableQueryLog();
        $bi_conexion = DB::connection('pgsql');

        $resultados = $bi_conexion->table('datamart_ventas_2025 as dv')
            ->select(
                'dv.idsucursal AS id',
                DB::raw('SUM(dv.importe_subtotal) AS anterior')
                //'fecha_documento as fecha'
            )
            ->leftJoin('pla_fechas_equivalentes as f', DB::raw('COALESCE(dv.fecha_documento, dv.fecha_documento)'), '=', 'f.fecha')
            ->leftJoin('datamart_calendario as c', DB::raw('COALESCE(f.fecha_equivalente, dv.fecha_documento)'), '=', 'c.fecha')
            ->leftJoin('datamart_logistica_productos as b', 'dv.codigo_producto', '=', 'b.codigo_producto')  // agregado
            // ->whereBetween('f.fecha', [$fecha_inicio, $fecha_fin])
            ->whereIn('f.fecha', function($query) use ($fecha_inicio, $fecha_fin) {
                $query->select('fecha')
                    ->from('pla_fechas_equivalentes')
                    ->whereBetween('fecha_equivalente', [$fecha_inicio, $fecha_fin]);
            })
            ->whereIn('dv.sucursal_marca_id', $marcas_select)
            ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']) // agregado
            ->where('dv.sucursal', '<>', 'SEDE CENTRAL'); // agregado

            if ($canal1 == true && $canal2 == false && $canal3 == false) {
                $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
                $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == false && $canal2 == true && $canal3 == false) {
                $resultados = $resultados->where('canal',  'E-COMMERCE');
            }
            if ($canal1 == false && $canal2 == false && $canal3 == true) {
                $resultados = $resultados->where('canal', '==', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == true && $canal2 == true && $canal3 == false) {
                $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
            }
            if ($canal1 == false && $canal2 == true && $canal3 == true) {
                $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
            }
            $resultados = $resultados->groupBy('dv.idsucursal', 'id')
            ->get();
        $consulta_sql = DB::getQueryLog();
        Log::info('Consulta SQL ventas_anterior por tienda:', $consulta_sql);

        return $resultados;
    }


    /*Esta es la funcion getReporteMarcaVenta, está bien*/
    public function getReporteMarcaVenta_original($fecha_inicio, $fecha_fin, $marcas_select, $canal1, $canal2, $canal3)
    {

        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";

        $sucursalesActivas = DB::table('config_sucursales')
        ->pluck('sucursal')
        ->toArray();

        set_time_limit(300);
        DB::enableQueryLog();

        $bi_conexion = DB::connection('pgsql');
        //  extraer los valores de la columna nombres 
        // $resultados = $bi_conexion->table('reporting_datamartventasclon')
        //     ->select('reporting_marcas.marca as nombre', 'reporting_datamartventasclon.sucursal_marca_id', DB::raw('SUM(reporting_datamartventasclon.importe_subtotal) AS importe_total_sum'))
        //     ->join('reporting_marcas', 'reporting_datamartventasclon.sucursal_marca_id', '=', 'reporting_marcas.id')
        //     ->whereNotNull('reporting_marcas.idmarca')
        //     ->whereBetween('reporting_datamartventasclon.fecha_documento', [$fecha_inicio, $fecha_fin])
        //     ->whereIn('reporting_datamartventasclon.sucursal_marca_id', $marcas_select)
        //     ->groupBy('reporting_marcas.marca', 'reporting_datamartventasclon.sucursal_marca_id')
        //     ->orderByDesc('importe_total_sum')
        //     ->get();
        //

        $tempFecObsoleto = DB::table('datamart_logistica_movimientos_actual as pc3')
        ->join('datamart_ventas_actual as v', 'pc3.codigo_producto', '=', 'v.codigo_producto')
        ->select(
            'v.fecha_documento',
            'v.codigo_producto',
            DB::raw('MAX(pc3.fecha) as fecha_obs')
        )
        ->where('pc3.IDMOVIMIENTO', 57)
        ->whereBetween('v.fecha_documento', [$fecha_inicio, $fecha_fin])
        ->groupBy('v.fecha_documento', 'v.codigo_producto');

        $onlineCondition = $canal1 ? "AND vcc.sucursal NOT LIKE '%ONLINE%'" : "";

        $resultados = $bi_conexion->table('datamart_ventas_actual as dv')
            ->select(
                'dv.sucursal_marca_id AS id',
                'rm.marca as nombre',
                // DB::raw("case 
			    //         when dv.sucursal like '%OUTLET%'  then 'OUTLETS'            
			    //         when dv.sucursal like '%ONLINE%' then 'WEB'
			    //         when dv.sucursal like '%MCH%' then 'BOUTIQUES MCH'
			    //         when dv.sucursal like '%KORDA%' then 'BOUTIQUES KORDA'
			    //         when dv.sucursal like '%EXIT%' then 'BOUTIQUES EXIT'
			    //         when dv.sucursal like '%MILK%' then 'BOUTIQUES MILK'
			    //         when dv.sucursal like '%FINA%' then 'BOUTIQUES FINA'
			    //         when dv.sucursal like '%BLUES%' then 'BOUTIQUES BLUES'
			    //              else dv.sucursal end marca_agrupada"),
                DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
                DB::raw('MAX(vc.meta) AS meta'),
                DB::raw('SUM(dv.importe_subtotal) / MAX(vc.meta) * 100 AS logro'),
                //DB::raw('(SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / SUM(dv.importe_subtotal) AS gm'),
                DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
                DB::raw('MAX(vc.meta_contribucion) AS meta_contribucion'),
                DB::raw('CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL ELSE (SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / NULLIF("vc"."meta_contribucion", 0) END AS logro_c'),
                DB::raw('SUM(dv.cantidad) as unidades'),
                DB::raw('CASE WHEN SUM(dv.cantidad) = 0 THEN 0 else SUM(dv.importe_subtotal) / SUM(dv.cantidad) end AS precio_promedio'),
                DB::raw('count(DISTINCT dv.idtransaccion) AS ticket'),
                DB::raw('SUM(dv.importe_subtotal) / count(DISTINCT dv.idtransaccion) AS ticket_promedio'),
                DB::raw($fecha_inicio . " AS fecha_inicio"),
                DB::raw($fecha_fin . " AS fecha_fin"),
                DB::raw('case when SUM(dv.importe_subtotal) = 0 then 0 
                    ELSE(SUM(dv.importe_subtotal) - sum(
                    case b.iditem_obsolescencia
                        when \'OBSOLETO\' THEN (
                                                CASE WHEN ( select  pc3.fecha from datamart_logistica_movimientos_actual  pc3  where pc3.IDMOVIMIENTO = 57 and pc3.CODIGO_PRODUCTO  = dv.CODIGO_PRODUCTO order by FECHA limit 1) <= dv.fecha_documento then 0
                                                ELSE (
                                                    CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                                                    ELSE (
                                                        case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                                        ELSE (
                                                             COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                                             )
                                                        END )
                                                    END )
                                                END )
                    ELSE (
                        CASE dv.FLAG_NOTACREDITO WHEN \'S\' THEN (
                            CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                            ELSE (
                                case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                ELSE (
                                     COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                    )
                                END )
                            END ) * 1
                        ELSE (
                            CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                            ELSE (
                                case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                ELSE (
                                     COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                    )
                                END )
                            END )
                        END )
                    END ))* 100 / SUM(dv.importe_subtotal) end as gm'),
                    DB::raw("SUM(( case b.iditem_obsolescencia when 'OBSOLETO' THEN ( CASE WHEN (SELECT pc3.fecha
                                                                                                                                               FROM datamart_logistica_movimientos_actual pc3
                                                                                                                                               WHERE pc3.IDMOVIMIENTO = 57
                                                                                                                                                 AND pc3.CODIGO_PRODUCTO = dv.CODIGO_PRODUCTO
                                                                                                                                               ORDER BY FECHA
                                                                                                                                               LIMIT 1) <= dv.fecha_documento then 0 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) ELSE ( CASE dv.FLAG_NOTACREDITO WHEN 'S' THEN ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end ) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) * 1 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) END ) * cantidad) as costo_venta_neta")
            )

            ->leftjoin(
                DB::raw('(SELECT rm.id, vcc.codigo_marca, SUM(vcc.importe*mn.porcentaje) AS meta, SUM(vcc.importe * vcc.contribucion*mn.porcentaje) AS meta_contribucion 
                    FROM datamart_ventas_cuota_postgre vcc 
                    left join pla_meta_sucursal_marca_nuevo mn on mn.sucursal = vcc.sucursal 
                    INNER JOIN reporting_marcas rm ON rm.idmarca = vcc.codigo_marca
                    WHERE vcc.fecha BETWEEN ' . $fecha_inicio . ' AND ' . $fecha_fin . ' ' . $onlineCondition . '
                    GROUP BY rm.id, vcc.codigo_marca) vc'),'dv.sucursal_marca_id','=','vc.codigo_marca'
            )
            ->join('reporting_marcas as rm', 'dv.sucursal_marca_id', '=', 'rm.idmarca')
            // ->leftJoin('datamart_logistica_productos as b', 'dv.codigo_producto', '=', 'b.codigo_producto')
            ->leftJoin('datamart_logistica_productos as b', function ($join) {
                $join->on('dv.codigo_producto', '=', 'b.codigo_producto')
                    ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']);
            })
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            // ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO'])
            ->where('dv.sucursal', '<>', 'SEDE CENTRAL')
            ->where('dv.sucursal', 'not like', '%FALABELLA%')
            ->whereIn('dv.sucursal', $sucursalesActivas);

        // ->where('dv.categoria','<>','SERVICIOS GENERALES')
        // ->where('dv.marca','<>','SERVICIOS');
        if ($canal1 == true && $canal2 == false && $canal3 == false) {
            $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
            $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == false && $canal2 == true && $canal3 == false) {
            $resultados = $resultados->where('canal',  'E-COMMERCE');
        }
        if ($canal1 == false && $canal2 == false && $canal3 == true) {
            $resultados = $resultados->where('canal', '==', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == true && $canal2 == true && $canal3 == false) {
            $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == false && $canal2 == true && $canal3 == true) {
            $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
        }
        //$resultados= $resultados->where('canal',  'E-COMMERCE');
        $resultados = $resultados->whereIn('dv.sucursal_marca_id', $marcas_select)
            ->groupBy('dv.sucursal_marca_id', 'rm.marca', 'vc.meta_contribucion')
            // ->groupBy('dv.sucursal_marca_id','marca_agrupada', 'rm.marca',  'vc.meta_contribucion')
            ->orderBy('importe_total_sum', 'DESC')
            ->get();

        //$resultados = $query->setBindings([$fecha_inicio, $fecha_fin])->get();
        // Obtiene el registro de consultas SQL
        $consulta_sql = DB::getQueryLog();
        // Registra la consulta SQL en el log
        Log::info('Consulta SQL REORTE:', $consulta_sql);

        return $resultados;
    }



     public function getReporteMarcaVenta($fecha_inicio, $fecha_fin, $marcas_select, $canal1, $canal2, $canal3)
    {
 
        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";
 
        $sucursalesActivas = DB::table('config_sucursales')
        ->pluck('sucursal')
        ->toArray();
 
        set_time_limit(300);
        DB::enableQueryLog();
 
        $bi_conexion = DB::connection('pgsql');
        //  extraer los valores de la columna nombres 
        // $resultados = $bi_conexion->table('reporting_datamartventasclon')
        //     ->select('reporting_marcas.marca as nombre', 'reporting_datamartventasclon.sucursal_marca_id', DB::raw('SUM(reporting_datamartventasclon.importe_subtotal) AS importe_total_sum'))
        //     ->join('reporting_marcas', 'reporting_datamartventasclon.sucursal_marca_id', '=', 'reporting_marcas.id')
        //     ->whereNotNull('reporting_marcas.idmarca')
        //     ->whereBetween('reporting_datamartventasclon.fecha_documento', [$fecha_inicio, $fecha_fin])
        //     ->whereIn('reporting_datamartventasclon.sucursal_marca_id', $marcas_select)
        //     ->groupBy('reporting_marcas.marca', 'reporting_datamartventasclon.sucursal_marca_id')
        //     ->orderByDesc('importe_total_sum')
        //     ->get();
        //
 
        $tempFecObsoleto = DB::table('datamart_logistica_movimientos_actual as pc3')
        ->join('datamart_ventas_actual as v', 'pc3.codigo_producto', '=', 'v.codigo_producto')
        ->select(
            'v.fecha_documento',
            'v.codigo_producto',
            DB::raw('MAX(pc3.fecha) as fecha_obs')
        )
        ->where('pc3.IDMOVIMIENTO', 57)
        ->whereBetween('v.fecha_documento', [$fecha_inicio, $fecha_fin])
        ->groupBy('v.fecha_documento', 'v.codigo_producto');
 
        $onlineCondition = $canal1 ? "AND vcc.sucursal NOT LIKE '%ONLINE%'" : "";
 
        $resultados = $bi_conexion->table('datamart_ventas_actual as dv')
            ->select(
                'dv.sucursal_marca_id AS id',
                'rm.marca as nombre',
                // DB::raw("case 
			    //         when dv.sucursal like '%OUTLET%'  then 'OUTLETS'            
			    //         when dv.sucursal like '%ONLINE%' then 'WEB'
			    //         when dv.sucursal like '%MCH%' then 'BOUTIQUES MCH'
			    //         when dv.sucursal like '%KORDA%' then 'BOUTIQUES KORDA'
			    //         when dv.sucursal like '%EXIT%' then 'BOUTIQUES EXIT'
			    //         when dv.sucursal like '%MILK%' then 'BOUTIQUES MILK'
			    //         when dv.sucursal like '%FINA%' then 'BOUTIQUES FINA'
			    //         when dv.sucursal like '%BLUES%' then 'BOUTIQUES BLUES'
			    //              else dv.sucursal end marca_agrupada"),
                DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
                DB::raw('MAX(vc.meta) AS meta'),
                DB::raw('SUM(dv.importe_subtotal) / MAX(vc.meta) * 100 AS logro'),
                //DB::raw('(SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / SUM(dv.importe_subtotal) AS gm'),
                DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
                DB::raw('MAX(vc.meta_contribucion) AS meta_contribucion'),
                DB::raw('CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL ELSE (SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / NULLIF("vc"."meta_contribucion", 0) END AS logro_c'),
                DB::raw('SUM(dv.cantidad) as unidades'),
                DB::raw('CASE WHEN SUM(dv.cantidad) = 0 THEN 0 else SUM(dv.importe_subtotal) / SUM(dv.cantidad) end AS precio_promedio'),
                DB::raw('count(DISTINCT dv.idtransaccion) AS ticket'),
                DB::raw('SUM(dv.importe_subtotal) / count(DISTINCT dv.idtransaccion) AS ticket_promedio'),
                DB::raw($fecha_inicio . " AS fecha_inicio"),
                DB::raw($fecha_fin . " AS fecha_fin"),
                DB::raw('case when SUM(dv.importe_subtotal) = 0 then 0 
                    ELSE(SUM(dv.importe_subtotal) - sum(
                    case b.iditem_obsolescencia
                        when \'OBSOLETO\' THEN (
                                                CASE WHEN ( select  pc3.fecha from datamart_logistica_movimientos_actual  pc3  where pc3.IDMOVIMIENTO = 57 and pc3.CODIGO_PRODUCTO  = dv.CODIGO_PRODUCTO order by FECHA limit 1) <= dv.fecha_documento then 0
                                                ELSE (
                                                    CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                                                    ELSE (
                                                        case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                                        ELSE (
                                                             COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                                             )
                                                        END )
                                                    END )
                                                END )
                    ELSE (
                        CASE dv.FLAG_NOTACREDITO WHEN \'S\' THEN (
                            CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                            ELSE (
                                case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                ELSE (
                                     COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                    )
                                END )
                            END ) * 1
                        ELSE (
                            CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                            ELSE (
                                case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                ELSE (
                                     COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                    )
                                END )
                            END )
                        END )
                    END ))* 100 / SUM(dv.importe_subtotal) end as gm'),
                    DB::raw("SUM(( case b.iditem_obsolescencia when 'OBSOLETO' THEN ( CASE WHEN (SELECT pc3.fecha
                                                                                                                                               FROM datamart_logistica_movimientos_actual pc3
                                                                                                                                               WHERE pc3.IDMOVIMIENTO = 57
                                                                                                                                                 AND pc3.CODIGO_PRODUCTO = dv.CODIGO_PRODUCTO
                                                                                                                                               ORDER BY FECHA
                                                                                                                                               LIMIT 1) <= dv.fecha_documento then 0 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) ELSE ( CASE dv.FLAG_NOTACREDITO WHEN 'S' THEN ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end ) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) * 1 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) END ) * cantidad) as costo_venta_neta")
            )
 
            ->leftjoin(
                DB::raw('(SELECT rm.id, vcc.codigo_marca, SUM(vcc.importe*mn.porcentaje) AS meta, SUM(vcc.importe * vcc.contribucion*mn.porcentaje) AS meta_contribucion 
                    FROM datamart_ventas_cuota_postgre vcc 
                    left join pla_meta_sucursal_marca_nuevo mn on mn.sucursal = vcc.sucursal 
                    INNER JOIN reporting_marcas rm ON rm.idmarca = vcc.codigo_marca
                    WHERE vcc.fecha BETWEEN ' . $fecha_inicio . ' AND ' . $fecha_fin . ' ' . $onlineCondition . '
                    GROUP BY rm.id, vcc.codigo_marca) vc'),'dv.sucursal_marca_id','=','vc.codigo_marca'
            )
            ->join('reporting_marcas as rm', 'dv.sucursal_marca_id', '=', 'rm.idmarca')
            // ->leftJoin('datamart_logistica_productos as b', 'dv.codigo_producto', '=', 'b.codigo_producto')
            ->leftJoin('datamart_logistica_productos as b', function ($join) {
                $join->on('dv.codigo_producto', '=', 'b.codigo_producto')
                    ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']);
            })
            ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
            // ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO'])
            ->where('dv.sucursal', '<>', 'SEDE CENTRAL')
            ->where('dv.sucursal', 'not like', '%FALABELLA%')
            ->whereIn('dv.sucursal', $sucursalesActivas);
 
        // ->where('dv.categoria','<>','SERVICIOS GENERALES')
        // ->where('dv.marca','<>','SERVICIOS');
        if ($canal1 == true && $canal2 == false && $canal3 == false) {
            $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
            $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == false && $canal2 == true && $canal3 == false) {
            $resultados = $resultados->where('canal',  'E-COMMERCE');
        }
        if ($canal1 == false && $canal2 == false && $canal3 == true) {
            $resultados = $resultados->where('canal', '==', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == true && $canal2 == true && $canal3 == false) {
            // ANTES: where('canal', '!=', 'TIENDAS POR DEPARTAMENTO')
            // Esto mezclaba boutiques + online en la misma fila por marca.
            // Ahora: igual que "canal1 solo", para que esta query traiga
            // SOLO boutiques. La fila "WEB" (online) se agrega aparte,
            // más abajo, como una fila resumen separada.
            $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
            $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == false && $canal2 == true && $canal3 == true) {
            $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
        }
        //$resultados= $resultados->where('canal',  'E-COMMERCE');
        $resultados = $resultados->whereIn('dv.sucursal_marca_id', $marcas_select)
            ->groupBy('dv.sucursal_marca_id', 'rm.marca', 'vc.meta_contribucion')
            // ->groupBy('dv.sucursal_marca_id','marca_agrupada', 'rm.marca',  'vc.meta_contribucion')
            ->orderBy('importe_total_sum', 'DESC')
            ->get();
 
        //$resultados = $query->setBindings([$fecha_inicio, $fecha_fin])->get();
        // Obtiene el registro de consultas SQL
        $consulta_sql = DB::getQueryLog();
        // Registra la consulta SQL en el log
        Log::info('Consulta SQL REORTE:', $consulta_sql);
 
        // ============================================================
        // BLOQUE NUEVO: cuando se seleccionan Boutiques (canal1) y
        // Online (canal2) al mismo tiempo, se agrega una fila extra
        // "WEB" con el total de E-COMMERCE (todas las marcas sumadas
        // en una sola fila).
        //
        // Es la MISMA query que "canal2 solo" (mismo SELECT completo,
        // incluyendo gm y costo_venta_neta), pero SIN agrupar por marca,
        // para que SUM()/MAX()/COUNT(DISTINCT) agreguen sobre el total
        // y Postgres devuelva una única fila ya calculada correctamente
        // (evita tener que recalcular a mano en PHP fórmulas con
        // subconsultas correlacionadas).
        // ============================================================
        if ($canal1 == true && $canal2 == true && $canal3 == false) {
 
            $onlineConditionWeb = ""; // igual que cuando solo canal2 está activo
 
            $filaWeb = $bi_conexion->table('datamart_ventas_actual as dv')
                ->select(
                    DB::raw("'WEB' AS id"),
                    DB::raw("'WEB' as nombre"),
                    DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
                    DB::raw('MAX(vc.meta) AS meta'),
                    DB::raw('SUM(dv.importe_subtotal) / MAX(vc.meta) * 100 AS logro'),
                    DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
                    DB::raw('MAX(vc.meta_contribucion) AS meta_contribucion'),
                    DB::raw('CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL ELSE (SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / NULLIF(MAX("vc"."meta_contribucion"), 0) END AS logro_c'),
                    DB::raw('SUM(dv.cantidad) as unidades'),
                    DB::raw('CASE WHEN SUM(dv.cantidad) = 0 THEN 0 else SUM(dv.importe_subtotal) / SUM(dv.cantidad) end AS precio_promedio'),
                    DB::raw('count(DISTINCT dv.idtransaccion) AS ticket'),
                    DB::raw('SUM(dv.importe_subtotal) / count(DISTINCT dv.idtransaccion) AS ticket_promedio'),
                    DB::raw($fecha_inicio . " AS fecha_inicio"),
                    DB::raw($fecha_fin . " AS fecha_fin"),
                    DB::raw('case when SUM(dv.importe_subtotal) = 0 then 0 
                        ELSE(SUM(dv.importe_subtotal) - sum(
                        case b.iditem_obsolescencia
                            when \'OBSOLETO\' THEN (
                                                    CASE WHEN ( select  pc3.fecha from datamart_logistica_movimientos_actual  pc3  where pc3.IDMOVIMIENTO = 57 and pc3.CODIGO_PRODUCTO  = dv.CODIGO_PRODUCTO order by FECHA limit 1) <= dv.fecha_documento then 0
                                                    ELSE (
                                                        CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                                                        ELSE (
                                                            case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                                            ELSE (
                                                                 COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                                                 )
                                                            END )
                                                        END )
                                                    END )
                        ELSE (
                            CASE dv.FLAG_NOTACREDITO WHEN \'S\' THEN (
                                CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                                ELSE (
                                    case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                    ELSE (
                                         COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                        )
                                    END )
                                END ) * 1
                            ELSE (
                                CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                                ELSE (
                                    case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                    ELSE (
                                         COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                        )
                                    END )
                                END )
                            END )
                        END ))* 100 / SUM(dv.importe_subtotal) end as gm'),
                        DB::raw("SUM(( case b.iditem_obsolescencia when 'OBSOLETO' THEN ( CASE WHEN (SELECT pc3.fecha
                                                                                                                                                   FROM datamart_logistica_movimientos_actual pc3
                                                                                                                                                   WHERE pc3.IDMOVIMIENTO = 57
                                                                                                                                                     AND pc3.CODIGO_PRODUCTO = dv.CODIGO_PRODUCTO
                                                                                                                                                   ORDER BY FECHA
                                                                                                                                                   LIMIT 1) <= dv.fecha_documento then 0 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) ELSE ( CASE dv.FLAG_NOTACREDITO WHEN 'S' THEN ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end ) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) * 1 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) END ) * cantidad) as costo_venta_neta")
                )
                ->leftjoin(
                    DB::raw('(SELECT rm.id, vcc.codigo_marca, SUM(vcc.importe*mn.porcentaje) AS meta, SUM(vcc.importe * vcc.contribucion*mn.porcentaje) AS meta_contribucion 
                        FROM datamart_ventas_cuota_postgre vcc 
                        left join pla_meta_sucursal_marca_nuevo mn on mn.sucursal = vcc.sucursal 
                        INNER JOIN reporting_marcas rm ON rm.idmarca = vcc.codigo_marca
                        WHERE vcc.fecha BETWEEN ' . $fecha_inicio . ' AND ' . $fecha_fin . ' ' . $onlineConditionWeb . '
                        GROUP BY rm.id, vcc.codigo_marca) vc'),'dv.sucursal_marca_id','=','vc.codigo_marca'
                )
                ->join('reporting_marcas as rm', 'dv.sucursal_marca_id', '=', 'rm.idmarca')
                ->leftJoin('datamart_logistica_productos as b', function ($join) {
                    $join->on('dv.codigo_producto', '=', 'b.codigo_producto')
                        ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']);
                })
                ->whereBetween('dv.fecha_documento', [$fecha_inicio, $fecha_fin])
                ->where('dv.sucursal', '<>', 'SEDE CENTRAL')
                ->where('dv.sucursal', 'not like', '%FALABELLA%')
                ->whereIn('dv.sucursal', $sucursalesActivas)
                ->where('canal', 'E-COMMERCE')
                ->whereIn('dv.sucursal_marca_id', $marcas_select)
                ->first();
            // Nota: sin groupBy aquí a propósito, para que todo el
            // resultado E-COMMERCE colapse en una sola fila (una sola
            // fila implícita), por eso se usa ->first() en vez de ->get().
 
            // Importante: si no hubo NINGUNA venta E-COMMERCE en el
            // periodo (cero filas), SUM()/COUNT() devuelven NULL/0 y
            // las divisiones dentro del SELECT (ej. SUM/MAX(vc.meta))
            // pueden quedar en NULL en vez de 0. Para mantener la fila
            // WEB siempre visible (con ceros) y consistente con
            // getImporteAnioAterior, se normalizan los campos numéricos
            // aquí en vez de omitir la fila.
            if ($filaWeb === null) {
                $filaWeb = new \stdClass();
                $filaWeb->id = 'WEB';
                $filaWeb->nombre = 'WEB';
            }
            if ($filaWeb->importe_total_sum === null) {
                $filaWeb->importe_total_sum = 0;
                $filaWeb->meta = $filaWeb->meta ?? 0;
                $filaWeb->logro = 0;
                $filaWeb->contribucion = 0;
                $filaWeb->meta_contribucion = $filaWeb->meta_contribucion ?? 0;
                $filaWeb->logro_c = null;
                $filaWeb->unidades = 0;
                $filaWeb->precio_promedio = 0;
                $filaWeb->ticket = 0;
                $filaWeb->ticket_promedio = 0;
                $filaWeb->gm = 0;
                $filaWeb->costo_venta_neta = 0;
            }
 
            $resultados->push($filaWeb);
 
            $consulta_sql = DB::getQueryLog();
            Log::info('Consulta SQL REORTE (con fila WEB):', $consulta_sql);
        }
 
        return $resultados;
    }
 
    

    /**Funcion prueba para traer datos desde la API */
    public function getDatosApi($fecha_inicio, $fecha_fin, $marcas_select, $canal1, $canal2, $canal3, $type)
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
        $solicitud = [
            "type" => $type,
            "fecha_inicio" => $fecha_inicio,
            "fecha_fin" => $fecha_fin,
            "marcas_select" => $marcas_select,
            "canal1" => $canal1,
            "canal2" => $canal2,
            "canal3" => $canal3,
            //"marca_id" => "12",
        ];
        //$prueba = prueba("esto es una preuba");
        //dd($prueba);
        $rpta = json_decode(rest_api($solicitud, get_url_api_rest_reporte(), "POST", 300, $http_header), true);
        //dd(rest_api($solicitud, get_url_api_rest_reporte(), "POST", 300, $http_header));
        //dd($rpta);
        return $rpta;
        //dd($token);

        // $token = rest_api_token();
        // if (!in_array('Bearer', $token)) {
        //     abort(403);
        // }
        // $http_header = [
        //     "Authorization: {$token['token_type']} {$token['access_token']}",
        //     //"Authorization: Beaver eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiIsImp0aSI6ImE2NjAyZmE4YWVhZTZjOTg1ZGI2MWRmYTQzMTllOGZmZWMxMTk4NmFkMGMxNTZlMzRjZmY4MzBiNzdmMDkyNjhjYmY3YmE1ZWQ3MTVmYTQzIn0.eyJhdWQiOiI3IiwianRpIjoiYTY2MDJmYThhZWFlNmM5ODVkYjYxZGZhNDMxOWU4ZmZlYzExOTg2YWQwYzE1NmUzNGNmZjgzMGI3N2YwOTI2OGNiZjdiYTVlZDcxNWZhNDMiLCJpYXQiOjE2OTcxMjkwNjEsIm5iZiI6MTY5NzEyOTA2MSwiZXhwIjoxNjk4NDI1MDYwLCJzdWIiOiIiLCJzY29wZXMiOltdfQ.hxeY-KsGnyV6MWxjgnVWuiH-jSuSK0iwNLlBs77s1Gd2FIztzQVAU-hDgc3c22n-bBkcKOzhx1QcXMXZILzgnBUyXOU7Xn5xCDssdbV-l54f49eBTqAEbRReava8SrhzjovIJxmdLGCq5hOniTNqFilHtl-vYypTc4e5tiPV32HiXoBprGC3e30gMzDGC-5NChG0i_QdtRUJVunsvRNAfKl_r83-z8X7gT1e_PZDmwyLi5K6ZgflhNuiOsDMlQY5mMXo-dNWmg6aAI9-1vWhz_GawvTnKOD6M9XW-aSmOdEe3261I4QJOsjEAgMqZoV-54VC4du4fEJ3-mrLtXyucI3wCdDVxJugJPzNaMisDY6mlEtUeSE8v_U14ypg6ZCTKp4xai6ev3YCHvj8VrxFpPDVgbEplrepUW4LDbM7Wiv-PaJw2wIaDLWbEUTpiJgJNJzrgy4DX4d7nhXHccptllKZyVfZH3_S21EgkS2pxQmFCmZZXTLHpx8btfHKXmd3A-N97shpzWSbxVrGtGNnpZKqQFtrK4AKp0ci0rqohR6CW85-TiQNJ1KxNB6V5wirDWpWQ9XBFR7Z8cB4QcEWgGpJVubpNkK3gR7pHiq-gWYYFR87-_sFj6ylIkqDDuH2gPPz4fCId-TuBFNMZOyTyhBmT_sm9LLWgF6rlPqMLSg",
        //     "Content-Type: application/json",
        // ];
        // $solicitud = [
        //     "type" => "lista-orden-compra2",
        //     //"codigo_usuario" => auth()->user()->identify,
        //     "codigo_usuario" => "fperez",
        //     // "env" => "production",
        // ];


        // $rpta = json_decode(rest_api($solicitud, get_url_api_rest(), "POST", 30, $http_header), true);

        //dd(rest_api($solicitud, get_url_api_rest(), "POST", 30, $http_header));
    }

    public function getdatamart_directo($fecha_inicio, $fecha_fin, $marcas_select, $canal1, $canal2, $canal3, $type)
    {
        $sql_conexion = DB::connection('sqlsrv');
    }

  

    public function getReporteVentaTienda($fecha_inicio, $fecha_fin, $tiendas_select, $canal1, $canal2, $canal3)
    {

        //var_dump($tiendas_select);
        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";
        $onlineCondition = $canal1 ? "AND vcc.sucursal NOT LIKE '%ONLINE%'" : "";

        $bi_conexion = DB::connection('pgsql');
        $resultados = $bi_conexion->table('datamart_ventas_actual AS dv')
            ->select(
                'dv.idsucursal AS id',
                'dv.sucursal AS nombre',
                'dv.sucursal_marca_id',
                DB::raw('SUM(dv.importe_subtotal) AS importe_total_sum'),
                'vc.meta',
                //DB::raw('dv.importe_subtotal'), //GM
                // DB::raw('SUM(dv.importe_subtotal) / vc.meta * 100 AS logro'), //204-11-12
                DB::raw('COALESCE(SUM(dv.importe_subtotal) / NULLIF(vc.meta, 0) * 100, 0) AS logro'),
                //DB::raw('SUM(dv.importe_subtotal) / vc.meta * 100 AS logro'),
                //DB::raw('CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL ELSE (SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / NULLIF(SUM(dv.importe_subtotal), 0) END AS gm'),
                DB::raw('SUM(dv.importe_subtotal) - SUM(dv.costo_venta) AS contribucion'),
                'vc.meta_contribucion',
                // DB::raw('CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL ELSE (SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / NULLIF("vc"."meta_contribucion", 0) END AS logro_c'), //204-11-12
                DB::raw('CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL ELSE (SUM(dv.importe_subtotal) - SUM(dv.costo_venta)) * 100 / NULLIF("vc"."meta_contribucion", 0) END AS logro_c'),
                DB::raw('SUM(dv.cantidad) as unidades'),
                // DB::raw(' CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL 
                // ELSE (SUM(dv.importe_subtotal) / SUM(dv.cantidad)) END AS precio_promedio'), //204-11-12
                DB::raw('CASE WHEN SUM(dv.importe_subtotal) = 0 THEN NULL 
                ELSE (SUM(dv.importe_subtotal) / NULLIF(SUM(dv.cantidad), 0)) 
                END AS precio_promedio'),
                DB::raw('count(DISTINCT dv.idtransaccion) AS ticket'),
                // DB::raw('SUM(dv.importe_subtotal) / count(DISTINCT dv.idtransaccion) AS ticket_promedio'), //204-11-12
                DB::raw('COALESCE(SUM(dv.importe_subtotal) / NULLIF(COUNT(DISTINCT dv.idtransaccion), 0), 0) AS ticket_promedio'),
                DB::raw($fecha_inicio . " AS fecha_inicio"),
                DB::raw($fecha_fin . " AS fecha_fin"),
                DB::raw('case WHEN SUM(dv.importe_subtotal) = 0 THEN NULL 
                else ((SUM(dv.importe_subtotal) - sum(
                    case b.iditem_obsolescencia
                        when \'OBSOLETO\' THEN (
                                                CASE WHEN ( select  pc3.fecha from datamart_logistica_movimientos_actual  pc3  where pc3.IDMOVIMIENTO = 57 and pc3.CODIGO_PRODUCTO  = dv.CODIGO_PRODUCTO order by FECHA limit 1) <= dv.fecha_documento then 0
                                                ELSE (
                                                    CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                                                    ELSE (
                                                        case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                                        ELSE (
                                                             COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                                             )
                                                        END )
                                                    END )
                                                END )
                    ELSE (
                        CASE dv.FLAG_NOTACREDITO WHEN \'S\' THEN (
                            CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                            ELSE (
                                case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                ELSE (
                                     COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                    )
                                END )
                            END ) * 1
                        ELSE (
                            CASE B.TIPO_COMPRA WHEN \'MUESTRA\' THEN 0 WHEN \'CONSIGNACION\' THEN 0
                            ELSE (
                                case motivo when \'REBATE TXD\' THEN 0 WHEN \'DESCUENTO DE PRECIO\' THEN 0
                                ELSE (
                                     COALESCE((select costo_unitario_mn from dm_productos_costos dpc  where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0)
                                    )
                                END )
                            END )
                        END )
                    END ))* 100 / NULLIF(SUM(dv.importe_subtotal), 0)) end as gm'),
                 DB::raw("SUM(( case b.iditem_obsolescencia when 'OBSOLETO' THEN ( CASE WHEN (SELECT pc3.fecha
                                                                                                                                               FROM datamart_logistica_movimientos_actual pc3
                                                                                                                                               WHERE pc3.IDMOVIMIENTO = 57
                                                                                                                                                 AND pc3.CODIGO_PRODUCTO = dv.CODIGO_PRODUCTO
                                                                                                                                               ORDER BY FECHA
                                                                                                                                               LIMIT 1) <= dv.fecha_documento then 0 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto =b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) ELSE ( CASE dv.FLAG_NOTACREDITO WHEN 'S' THEN ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end ) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) * 1 ELSE ( CASE B.TIPO_COMPRA WHEN 'MUESTRA' THEN 0 ELSE ( case motivo when 'REBATE TXD' THEN 0 WHEN 'DESCUENTO DE PRECIO' THEN 0 ELSE ( CASE ( COALESCE(( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ),0) ) WHEN 0 THEN ( COALESCE((select costo_unitario_mn from public.dm_productos_costos where idempresa=dv.idempresa and codigo_producto=b.codigo_producto),0) ) ELSE ( (COALESCE(importe_subtotal,0) / case dv.cantidad when 0 then 1 else dv.cantidad end) * ( ( select x1.porc_asegurado from public.dm_consignacion_parametro_persona x1 where b.idpersona = x1.idpersona ) / 100  ) ) END ) END ) END ) END ) END ) * cantidad) as costo_venta_neta")
            )
            ->join(DB::raw('(SELECT vcc.sucursal, SUM(vcc.importe*mn.porcentaje) AS meta, SUM(vcc.importe*vcc.contribucion*mn.porcentaje) AS meta_contribucion
                    FROM datamart_ventas_cuota_postgre AS vcc
                    left join pla_meta_sucursal_marca_nuevo as mn on mn.sucursal = vcc.sucursal 
                    WHERE vcc.fecha BETWEEN ' . $fecha_inicio . ' AND ' . $fecha_fin . ' ' . $onlineCondition . '
                    GROUP BY vcc.sucursal) AS vc'), 'dv.sucursal', '=', 'vc.sucursal')
            // ->leftJoin('datamart_logistica_productos as b', 'dv.codigo_producto', '=', 'b.codigo_producto')
            ->leftJoin('datamart_logistica_productos as b', function ($join) {
                $join->on('dv.codigo_producto', '=', 'b.codigo_producto')
                    ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO']);
            })
            ->whereRaw("dv.fecha_documento BETWEEN " . $fecha_inicio . " AND $fecha_fin")
            // ->whereNotIn('b.linea', ['BOLSAS', 'ADMINISTRATIVO'])
            ->whereIn('dv.sucursal_marca_id', $tiendas_select);
        if ($canal1 == true && $canal2 == false && $canal3 == false) {
            $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
            $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == false && $canal2 == true && $canal3 == false) {
            $resultados = $resultados->where('canal',  'E-COMMERCE');
        }
        if ($canal1 == false && $canal2 == false && $canal3 == true) {
            $resultados = $resultados->where('canal', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == true && $canal2 == true && $canal3 == false) {
            $resultados = $resultados->where('canal', '!=', 'TIENDAS POR DEPARTAMENTO');
        }
        if ($canal1 == false && $canal2 == true && $canal3 == true) {
            $resultados = $resultados->where('canal', '!=', 'E-COMMERCE');
        }
        $resultados = $resultados->groupBy('dv.idsucursal', 'dv.sucursal', 'dv.sucursal_marca_id', 'vc.meta', 'vc.meta_contribucion')
            ->orderBy('importe_total_sum', 'DESC')
            ->get();

    

        return $resultados;
    }



    public function getMetastienda($reporte)
    {

        $idsucursales = [];
        foreach ($reporte as $value) {
            $idsucursales = $value->nombre;
        }

        $bi_conexion = DB::connection('pgsql');
        $resultados = $bi_conexion->table('datamart_ventas_cuota_postgre')
            ->select(DB::raw('sum(importe) AS importe'), 'sucursal')
            ->whereIn('sucursal', $idsucursales)
            ->orderBy('importe', 'DESC')
            ->get();

        return $resultados;
    }

    public function getMarcas()
    {
        $bi_conexion = DB::connection('pgsql');
        $marcas = $bi_conexion->table('reporting_marcas')->select('*')->where('estado','true')
            ->get();
        return $marcas;
    }

    public function updateSucursales(Request $request)
    {
        $seleccionadas = $request->input('sucursales', []);

        $actuales = DB::table('config_sucursales')
            ->pluck('sucursal')
            ->toArray();

        $paraInsertar = array_diff($seleccionadas, $actuales);
        $paraEliminar = array_diff($actuales, $seleccionadas);

        DB::transaction(function () use ($paraInsertar, $paraEliminar) {

            if (!empty($paraInsertar)) {
                DB::table('config_sucursales')->insert(
                    collect($paraInsertar)->map(fn($s) => [
                        'sucursal' => $s,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])->toArray()
                );
            }

            if (!empty($paraEliminar)) {
                DB::table('config_sucursales')
                    ->whereIn('sucursal', $paraEliminar)
                    ->delete();
            }
        });

        return response()->json(['status' => 'ok']);
    }

    public function getIDMarcas($marcas)
    {
        $marcas_select = [];
        foreach ($marcas as $value) {
            $marcas_select[] = $value->idmarca;
        }
        return $marcas_select;
    }

    public function ValidarPermisos($id) {}

    public function getLocatioIdTiendas($fecha_inicio, $fecha_fin, $tiendas_select)
    {

        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";

        $bi_conexion = DB::connection('pgsql');
        // $respuesta = $bi_conexion->table('location_id_tienda as lit')
        //     ->join('report_enter as re', 'lit.location', '=', 're.group_id')
        //     ->whereBetween('re.start_datetime', ['2023-06-10', '2023-06-10'])
        //     ->whereIn('idtienda', $tiendas_select)
        //     ->select('lit.location', 'lit.idtienda', 're.start_datetime', 're.value')
        //     ->get();

        $respuesta = $bi_conexion->table('location_id_tienda as lit')
            ->join('report_enter as re', 'lit.location', '=', 're.group_id')
            ->whereBetween('re.start_datetime', [$fecha_inicio, $fecha_fin])
            ->whereIn('lit.idtienda', $tiendas_select)
            ->groupBy('lit.idtienda')
            ->select('lit.idtienda AS id', DB::raw('SUM(re.value) as total'))
            ->get();

        return $respuesta;
    }

    public function getLocatioIdMarcas($fecha_inicio, $fecha_fin, $tiendas_select)
    {

        $fecha_inicio =  "'" . $fecha_inicio . "'";
        $fecha_fin = "'" . $fecha_fin . "'";
        set_time_limit(300);
        DB::enableQueryLog();
        $bi_conexion = DB::connection('pgsql');

        $respuesta = $bi_conexion->table('location_id_tienda as lit')
            ->join('report_enter as re', 'lit.location', '=', 're.group_id')
            ->whereBetween('re.start_datetime', [$fecha_inicio, $fecha_fin])
            ->whereIn('lit.idmarca', $tiendas_select)
            ->groupBy('lit.idmarca')
            ->select('lit.idmarca AS id', DB::raw('SUM(re.value) as total'))
            ->get();
        $consulta_sql = DB::getQueryLog();
        // Registra la consulta SQL en el log
        Log::info('Consulta SQL:', $consulta_sql);
        return $respuesta;
    }

    public function getapi(Request $request)
    {
        $fecha_inicio = $request->query('fecha_inicio');
        $fecha_fin = $request->query('fecha_fin');
        $marcas = $this->getMarcas();

        $respuesta = $this->getDatosApi($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), false, false, false, "get-reporte-ventas-realtime");
        //dd($respuesta);
        return $respuesta;
    }

    public function getapi_conteo(Request $request, $tipo, $fecha_inicio, $fecha_fin)
    {
        //  echo $fecha_inicio;
        // echo $tipo;
        //dd($tipo);
        // Datos para la solicitud de token
        $refresh_token = "eyJjdHkiOiJKV1QiLCJlbmMiOiJBMjU2R0NNIiwiYWxnIjoiUlNBLU9BRVAifQ.RQyb-uYmKuluD1rUZO8fI5XX1UDk0ozOOt1ISLJgSHw_-z-9xOylh3NihpUHbIzp_6FhEFWu9Hv3TAgPGZZT8SnTS05hlZ5QnRgU00Osd67JKeBrrOoEoRybjbbHrS8ZqgDs2Gg_sGSlveWTY2H609Ux2BshK_7bYB0axqbQxTJTcrTwFQjtuXwcdGIJva-dRoraMPpzfUbFbnOmdmjks4ZwX5gJKaQ_Ytui_tShq2vbDutyJt1vWkeRaWyKf34QXXbo7PcjK2UBnrS74QaljimGfNL-fkM6lABVbnFPR9Z0cwh-6iwW6Ygf64dJeEzJR1jlYJCTCD6ZjNr8tCs-og.-zfoI1XlOMeTQBEq.sVr2WTUNMjBq4RzVF0jZgX9Chgj29k3vPSQSLxvY6iuL8hhaW_HxEn_krYvfeHa4Y3u4rgJpSDppSh0-l-Sp8WhcgpbICuVQWiPU6B9aPmp0JDDbCnlL_OKKkWnRP5njFgcLUEZnARiRlhnBXRVjQAizzjzmBrVh5e_-rB2Z5sA7RNHFdNf38VplG-_h0f_pSQrKWXp55uRSfVMiNBWooUUq8VyEbAC-N9iDP92PEYI7-ArfzfVamLIuUqCRYuAIpo56Q7BQT0k9lQwseQJv5CnjY8x4IVPgHCKf1omfoOj00HawUjFDTDTRxywbRs3NpAtCDSxuTAjX23AydlkfmabC0x2aUJspAxq3JqWkZ98OeB03PMEo7ykuxZnuYtz4m--BNK4DEYcjn1kGmGjJildfqY7yi5mQDfP9urlqIWjACEanILaM4RmsuFODRL-4-gMYS-iScwMXW_Ym96q0X39Uou0OTc_PiNFbAL909pORWd9nfyXjMH-kV68W0Vc6hAWtdDDo4LWaR66_flppBGFjaLq5ltOE-qRNlz1Dchiu52EeUH0p28Ds44u3jjvmXGvf1qPPzGhGUWvmA0n6seBUnUzG9LJYg3y4_Akd9DvMzrTwev6MFBY4JtLVM6gGHODQ5DXzhgrK09w2XR0iy-lpZFA6KlhlOqMHc4cksZHsgPBelBUXiPibhFU7ee62ckzxoaOuh3tVft_IHf7EES6dlBpzCl3zaubcT7QHw0MZ7-fKddsbPkU4U9xu7pknjtzm--eMW6UcdxjMNpjERhDS1ZwUgsvgkkU25Y6AfXN_pIMqDa1tE86i-bK1NWzGC_MoQjeKTByOFNLhGAuzCwDMuq2I6oohBM8oxUVce7Maae577yWMBjuRT741dY8qyR8YxQ48E28G9Thoch_DCEvTRXpxnxbJl-8vc64dPsgOQiRmvBipbS8GI5OwZijROdV6REKHq-V8SB1p3_mW7fYgJOIvm4uviYyYEGT3Zxw9LpghRl4u5yUAFRYlqVVf71thT_tv6HPEZJYoohJXdM6zToAlONemmwI-jbLD6P49PPlR0yvdUBf2IRTIM03mVyRYwlSbUsG9AWi6bNlwiFWVe7lc2l65nvsnSo8kLSwziQ3jnoOQP4Sfj4fxW2OJjK6XMgU_ypP3KgqY0fpXK8BrD-zhd1-LTfVKHJbQ9jqvPuiDMtxxxpAMEE_-gA0X-nP-TZa0bMlivV_1OwfpVcLTgES6icu6XlFHb6TAP39b6Ex5Tb-6uMtOjN_L-lOA5eXxX0wGCCgBK4_PNPXfC8vdoy-WvijXYdL5Gy9Zio9LS5QLONM7dWolytzrEgl0VPKw-pwm34ZgoNXmjyfk8gV9JZFMjGCT.gOIef4qk3JCTQJODGInXTQ";
        $client_id = '63h4jubc7qvrg9f65c36vjp3bq';
        $redirect_uri = 'https: // login.tbretail.com/mytoken/index.html';

        // URL de la solicitud de token
        $token_url = 'https://auth.tbretail.com/oauth2/token';

        // Datos de la solicitud
        $data = http_build_query([
            'grant_type' => 'refresh_token',
            'client_id' => $client_id,
            'redirect_uri' => $redirect_uri,
            'refresh_token' => $refresh_token
        ]);

        // Configuración de la solicitud POST
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $data
            ]
        ];

        // Realizar la solicitud POST
        $context = stream_context_create($options);
        $response = file_get_contents($token_url, false, $context);

        // Decodificar la respuesta JSON
        $token_data = json_decode($response, true);

        // Extraer el access token
        $access_token = $token_data['access_token'];

        // Imprimir el access token
        //echo $access_token;

        // URL de la API
        $api_url = 'https://api.tbretail.com';
        // $current_date = date('Y-m-d');//comentado 2024-11-06
        $current_date_inicio = $fecha_inicio;
        $current_date_fin = $fecha_fin;
        // Datos de la solicitud
        $json_data = [
            'query' => '
            query {
                getData(
                    companies: []
                    brands: []
                    locations: [1566, 1569, 1570, 1571, 1572, 1573, 1575, 1577, 1578, 1579, 1580, 1581, 1586, 1587, 1588, 1617, 1618, 1619, 1620, 1630, 1631, 1632, 1633, 1634, 1635, 1636, 1637, 1638, 1639, 1640, 1643, 1644, 1645, 1646, 1647, 1648, 1651, 1652, 1653, 1655, 1657, 1658, 1661, 1662, 1663, 1665, 1666, 1672, 1674, 1678, 1696, 1697, 1699, 1709]
                    zones: []
                    start_date:  "' . $current_date_inicio . '"
                    end_date:  "' . $current_date_fin . '"
                    metrics: [{name: ENTERS, operation: SUM} ]
                    category: {dimension: MINUTE  interval: 15}
                    group: {dimension: LOCATION}
                ) {
                    series {
                        group
                        metric
                        data
                    }
                    categories
                }
            }
            '
        ];

        // Cabeceras de la solicitud
        $headers = [
            'Authorization: ' . $access_token,
            'Content-Type: application/json',
        ];

        // Realizar la solicitud POST a la API
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch); //esto es un json
        curl_close($ch);

        // Decodificar la respuesta JSON
        $data = json_decode($response, true);
        //dd($data);

        /** Tratamiento de los datos */

        $processedData = [];
        foreach ($data['data']['getData']['series'] as $item) {
            $group = $item['group'];
            $sumEnters = array_sum($item['data']);
            $processedData[] = ['group' => $group, 'sumEnters' => $sumEnters];
        }
        //$conteos = response()->json($processedData);
        $conteos = $processedData;
        //dd($conteos);
        $bi_conexion = DB::connection('pgsql');

        $respuesta = $bi_conexion->table('location_id_tienda')
            ->select('*')
            ->get();
        //echo $respuesta;
        //respuesta: [{"location":1569,"idtienda":240,"idmarca":12},{"location":1571,"idtienda":206,"idmarca":12}]

        $conteosPorMarca = []; // Array para almacenar las sumas de conteos por marca

        foreach ($respuesta as $rest) {
            foreach ($conteos as $conteo) {
                if ($rest->location == $conteo['group']) {
                    if ($tipo == 'marca') {
                        // Verificar si la marca ya está en el array
                        if (!isset($conteosPorMarca[$rest->idmarca])) {
                            $conteosPorMarca[$rest->idmarca] = 0; // Inicializar la suma en 0 si no existe
                        }
                        // Sumar el conteo al total de la marca
                        $conteosPorMarca[$rest->idmarca] += $conteo['sumEnters'];
                    }
                    if ($tipo == 'tienda') {
                        // Verificar si la marca ya está en el array
                        if (!isset($conteosPorMarca[$rest->idtienda])) {
                            $conteosPorMarca[$rest->idtienda] = 0; // Inicializar la suma en 0 si no existe
                        }
                        // Sumar el conteo al total de la marca
                        $conteosPorMarca[$rest->idtienda] += $conteo['sumEnters'];
                    }
                }
            }
        }

        // Ahora $conteosPorMarca contiene las sumas de conteos por cada idmarca
        $arrayConteosPorMarca = $conteosPorMarca;
        return response()->json($arrayConteosPorMarca);
        //bien pero quiero q se guarde como array

        //echo $respuesta;
    }

    public function getapiprueba()
    {


        $fecha_inicio =  date('Y-m-d');
        $fecha_fin = date('Y-m-d');

        $marcas = $this->getMarcas();
        $canal1= true;
        $canal2= false;
        $canal3= false;

        //$reporte = $this->getReporteMarcaVenta($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false);
        //$datos = $this->getDatosVentas($fecha_inicio, $fecha_fin);
        //$reporte_api = $this->getDatosApi($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), true, false, false, "get-reporte-marcas");
        //$reporte = collect(json_decode(json_encode($reporte_api)));
        //dd($reporte_api);
        //var_dump($reporte);
        //var_dump($prueba);
        $anio_anterior = $this->getImporteAnioAterior($fecha_inicio, $fecha_fin, $this->getIDMarcas($marcas), $canal1, $canal2, $canal3);

        //$idmarcas = $this->getIDMarcas($marcas);
        //var_dump($fecha_inicio);
        $option = 'marca';
        $fecha = $fecha_inicio . ' al ' . $fecha_fin;
        $titulo = "Detalle por Marcas";
        $filtroonline = 1;
        $filtroincluironline = 0;
        $filtrotxd = 0;

        $entradas  = $this->getLocatioIdTiendas($fecha_inicio, $fecha_fin, [297, 4]);
        //var_dump($entradas);
        //var_dump($prueba);
        return view('ver_mas2', compact('marcas', 'option', 'fecha', 'titulo', 'anio_anterior', 'entradas', 'filtroonline', 'filtroincluironline', 'filtrotxd'));
    }

    public function getfechaequivalente($fecha){
        $bi_conexion = DB::connection('pgsql');
        $fecha_equivalente = $bi_conexion->table('pla_fechas_equivalentes as dv')->select('fecha')->where('fecha_equivalente', $fecha)
            ->first();
        return $fecha_equivalente ? $fecha_equivalente->fecha : null;
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

    public function reporte_ventas()
    {
        return view('reporte_ventas');
    }   
}