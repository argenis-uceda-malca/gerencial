<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenSpout\Reader\Common\Creator\ReaderFactory;

class ReproteTxdController extends Controller
{
    protected $columnasRipley = [
        'Fecha'             => 'fecha',
        'Marca'             => 'marca',
        'Temporada'         => 'temporada',
        'Sucursal'          => 'sucursal',
        'Suma de Costo Venta Actual' => 'costo_vta',
        'Codigo Modelo'     => 'codigo_modelo',
        'Nombre Modelo'     => 'nombre_modelo',
        'Suma de Rebates Actual' => 'rebate_act',
        'Codigo Variacion'  => 'sku_txd',
        'Nombre Variacion'  => 'desc_sku',
        'Suma de Venta S/.' => 'vta_soles',
        'Suma de Venta Unid.' => 'vta_unds',
        'Suma de Contr. S/.'=> 'contr',
        'Suma de Stock S/.' => 'stock_soles',
        'Suma de Stock Und.'=> 'stock_unds',
    ];

    protected $columnasOechsle = [
        'PERIODO'             => 'fecha',
        'COD_OECHSLE'         => 'sku_txd',
        'DESCRIPCION_PRODUCTO'=> 'desc_sku',
        'MARCA'               => 'marca',
        'COD_LOCAL'           => 'cod_local',
        'DESCRIPCION_LOCAL'   => 'desc_local',
        'VTA_PERIODO_S'       => 'vta_act',
        'VTA_PERIODO_UNID'    => 'vta_unds',
        'INVENTARIO_S'        => 'stk_soles',
        'INVENTARIO_UNID'     => 'stk_unds',
    ];

    public function ejecutarPython(Request $request)
    {
        $tipo = $request->input('tipo', 1);
        $comando = base_path("run_python.sh {$tipo}");
        exec($comando, $salida, $codigoSalida);

        $logPath = base_path('log_laravel_python.txt');
        $logContent = file_exists($logPath) ? file_get_contents($logPath) : '';

        return response()->json([
            'codigo_salida' => $codigoSalida,
            'salida' => $salida,
            'log' => $logContent
        ]);
    }

    public function reportetxd()
    {
        return view('reporte_txd/reportetxd');
    }

    public function cargar_documentos()
    {
        return view('reporte_txd/cargar_documentos');
    }

    public function subir_documentos(Request $request)
    {
        Log::info('Dentro de subir:');
        Log::info($request->all());
        $request->validate([
            'archivo' => 'required',
            'archivo.*' => 'file|mimes:xlsx,xls,csv|max:20480',
        ]);

        if ($request->hasFile('archivo')) {
            foreach ($request->file('archivo') as $archivo) {
                $nombreOriginal = $archivo->getClientOriginalName();
                Log::info('Archivo recibido: ' . $nombreOriginal);

                if (stripos($nombreOriginal, 'ripley') !== false) {
                    $subcarpeta = 'ripley';
                } elseif (stripos($nombreOriginal, 'oechsle') !== false) {
                    $subcarpeta = 'oechsle';
                } else {
                    $subcarpeta = 'otros';
                }

                $ruta = $archivo->store("reporte_txd/$subcarpeta", 'public');
                $rutaCompleta = storage_path("app/public/$ruta");
                Log::info("Guardado en: $ruta");

                try {
                    if ($subcarpeta === 'ripley') {
                        $this->procesarRipley($rutaCompleta);
                    } elseif ($subcarpeta === 'oechsle') {
                        $this->procesarOechsle($rutaCompleta);
                    }
                } catch (\Exception $e) {
                    Log::error("Error procesando $nombreOriginal: " . $e->getMessage());
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Archivos guardados y procesados correctamente.']);
    }

    protected function procesarRipley($rutaCompleta)
    {
        Log::info("Procesando Ripley: $rutaCompleta");

        DB::table('automatizacion_temp_ripley_txd')->truncate();

        $reader = ReaderFactory::createFromFile($rutaCompleta);
        $reader->open($rutaCompleta);

        $cabeceras = null;
        $mapCol = [];
        $inserts = [];
        $total = 0;

        foreach ($reader->getSheetIterator() as $sheet) {
            if ($sheet->getName() !== 'TD1') continue;

            foreach ($sheet->getRowIterator() as $row) {
                $vals = $row->toArray();

                if ($cabeceras === null) {
                    $cabeceras = array_map('trim', $vals);
                    foreach ($cabeceras as $i => $col) {
                        if (isset($this->columnasRipley[$col])) {
                            $mapCol[$i] = $this->columnasRipley[$col];
                        }
                    }
                    continue;
                }

                $data = [];
                foreach ($mapCol as $i => $dbCol) {
                    $val = $vals[$i] ?? null;
                    if ($dbCol === 'fecha' && $val) {
                        if ($val instanceof \DateTimeInterface) {
                            $val = $val->format('Y-m-d');
                        } elseif (is_numeric($val)) {
                            $val = date('Y-m-d', ($val - 25569) * 86400);
                        } else {
                            $ts = strtotime(str_replace('/', '-', (string)$val));
                            $val = $ts ? date('Y-m-d', $ts) : null;
                        }
                    }
                    $data[$dbCol] = ($val !== null && $val !== '') ? $val : null;
                }
                $inserts[] = $data;
                $total++;

                if (count($inserts) >= 500) {
                    DB::table('automatizacion_temp_ripley_txd')->insert($inserts);
                    $inserts = [];
                }
            }
        }

        $reader->close();

        if (!empty($inserts)) {
            DB::table('automatizacion_temp_ripley_txd')->insert($inserts);
        }

        Log::info("Ripley procesado: $total filas.");
    }

    protected function procesarOechsle($rutaCompleta)
    {
        Log::info("Procesando Oechsle: $rutaCompleta");

        $delimiter = $this->detectarDelimitadorCSV($rutaCompleta);
        $rows = array_map(function ($line) use ($delimiter) {
            return str_getcsv($line, $delimiter);
        }, file($rutaCompleta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));

        if (empty($rows)) {
            Log::warning("Archivo Oechsle vacío.");
            return;
        }

        $columnasCSV = array_map('trim', $rows[0]);
        $mapCol = [];
        foreach ($columnasCSV as $i => $col) {
            if (isset($this->columnasOechsle[$col])) {
                $mapCol[$i] = $this->columnasOechsle[$col];
            }
        }

        $inserts = [];
        for ($r = 1; $r < count($rows); $r++) {
            if (count($rows[$r]) < count($columnasCSV)) continue;

            $data = [];
            foreach ($mapCol as $i => $dbCol) {
                $val = trim($rows[$r][$i] ?? '');
                if ($val === '' || $val === 'NA') continue;

                if ($dbCol === 'fecha') {
                    $partes = explode(' al ', $val);
                    $val = date('Y-m-d', strtotime(str_replace('/', '-', trim($partes[0]))));
                }
                $data[$dbCol] = $val;
            }
            if (!empty($data)) {
                $inserts[] = $data;
            }
        }

        if (empty($inserts)) {
            Log::warning("Sin datos válidos en Oechsle.");
            return;
        }

        DB::table('automatizacion_temp_oechsle_txd')->truncate();
        foreach (array_chunk($inserts, 500) as $chunk) {
            DB::table('automatizacion_temp_oechsle_txd')->insert($chunk);
        }

        Log::info("Oechsle insertado: " . count($inserts) . " filas.");
    }

    private function detectarDelimitadorCSV($ruta)
    {
        $sample = file_get_contents($ruta, false, null, 0, 2000);
        $delimiters = [',' => 0, ';' => 0, "\t" => 0];
        foreach ($delimiters as $d => &$count) {
            $count = substr_count($sample, $d);
        }
        unset($count);
        arsort($delimiters);
        return key($delimiters);
    }

    public function submit_txd(Request $request)
    {
    }
}
