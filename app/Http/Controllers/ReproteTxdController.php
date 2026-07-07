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



class ReproteTxdController extends Controller
{

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




    public function testPythonVersion()
    {
        // $output = shell_exec(base_path('.venv/bin/python3') . ' -m pip list');
        // return response()->json(['pip_list' => $output]);
        echo  base_path();
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

                // Detectar el destino dinámico según el nombre
                if (stripos($nombreOriginal, 'ripley') !== false) {
                    $subcarpeta = 'ripley';
                } elseif (stripos($nombreOriginal, 'oechsle') !== false) {
                    $subcarpeta = 'oechsle';
                } else {
                    $subcarpeta = 'otros'; // por si no coincide con ninguno
                }

                // Guardar en la subcarpeta correspondiente
                $ruta = $archivo->store("reporte_txd/$subcarpeta", 'public');

                Log::info("Guardado en: $ruta");
            }
        }

        return response()->json(['success' => true, 'message' => 'Archivos guardados correctamente.']);
    }


    public function submit_txd(Request $request){
        
    }
}
