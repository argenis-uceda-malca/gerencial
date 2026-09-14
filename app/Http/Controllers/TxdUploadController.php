<?php

namespace App\Http\Controllers;

use App\Services\Txd\TxdFileParser;
use App\Services\Txd\TxdLoaderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class TxdUploadController extends Controller
{
    public function __construct(
        private readonly TxdFileParser $parser,
        private readonly TxdLoaderService $loader,
    ) {
    }

    public function create()
    {
        return view('txd.upload');
    }

    /**
     * Recibe cualquier subconjunto de los 5 archivos y carga solo lo que llegó.
     * No exige los 5 juntos: se puede subir Oechsle esta semana y Ripley después,
     * por ejemplo, si llegan en momentos distintos.
     */
    public function store(Request $request)
    {
        $request->validate([
            'oechsle_venta'    => ['nullable', 'file', 'mimes:csv,txt'],
            'oechsle_stock'    => ['nullable', 'file', 'mimes:csv,txt'],
            'ripley'           => ['nullable', 'file', 'mimes:xlsx,xls'],
            'falabella_stock'  => ['nullable', 'file', 'mimes:xlsx,xls'],
            'falabella_ventas' => ['nullable', 'file', 'mimes:xlsx,xls'],
            'p_fecha_stock'    => ['nullable', 'date'],
            'ejecutar_pipeline' => ['nullable', 'boolean'],
        ]);

        $resumen = [];

        try {
            DB::transaction(function () use ($request, &$resumen) {
                if ($request->hasFile('oechsle_venta')) {
                    $rows = $this->parser->parseOechsle($request->file('oechsle_venta'));
                    $resumen['oechsle_venta'] = $this->loader->loadOechsle($rows);
                }

                if ($request->hasFile('oechsle_stock')) {
                    // Mismo destino que oechsle_venta: automatizacion_temp_oechsle_txd.
                    // Si ambos se suben juntos, se combinan antes de truncar+insertar
                    // para no perder uno de los dos con el truncate del otro.
                    $rows = $this->parser->parseOechsle($request->file('oechsle_stock'));
                    if (isset($resumen['oechsle_venta'])) {
                        $ventaRows = $this->parser->parseOechsle($request->file('oechsle_venta'));
                        $rows = $ventaRows->concat($rows);
                        $resumen['oechsle_venta'] = $this->loader->loadOechsle($rows);
                        unset($resumen['oechsle_stock']);
                    } else {
                        $resumen['oechsle_stock'] = $this->loader->loadOechsle($rows);
                    }
                }

                if ($request->hasFile('ripley')) {
                    $rows = $this->parser->parseRipley($request->file('ripley'));
                    $resumen['ripley'] = $this->loader->loadRipley($rows);
                }

                if ($request->hasFile('falabella_stock')) {
                    $rows = $this->parser->parseFalabellaStock($request->file('falabella_stock'));
                    $resumen['falabella_stock'] = $this->loader->loadFalabellaStock($rows);
                }

                if ($request->hasFile('falabella_ventas')) {
                    $rows = $this->parser->parseFalabellaVentas($request->file('falabella_ventas'));
                    $resumen['falabella_ventas'] = $this->loader->loadFalabellaVentas($rows);
                }
            });
        } catch (Throwable $e) {
            Log::error('Error cargando archivos TXD: ' . $e->getMessage(), ['exception' => $e]);
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'Error al procesar los archivos: ' . $e->getMessage()], 422);
            }
            return back()->withErrors(['archivo' => 'Error al procesar los archivos: ' . $e->getMessage()]);
        }

        if (empty($resumen)) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['message' => 'No se subió ningún archivo.'], 422);
            }
            return back()->withErrors(['archivo' => 'No se subió ningún archivo.']);
        }

        $mensaje = 'Cargado a staging: ' . collect($resumen)
            ->map(fn ($n, $k) => "{$k}={$n} filas")
            ->implode(', ');

        // El orquestador solo se dispara si el usuario lo pide explícitamente:
        // cargar a staging y ejecutar el pipeline son dos pasos separados a propósito,
        // para poder revisar los datos en staging antes de correr el reporte completo.
        if ($request->boolean('ejecutar_pipeline')) {
            try {
                $resultado = DB::selectOne(
                    'SELECT automatizacion_ejecutar_txd_completo(NULL, NULL, ?, ?) AS ok',
                    [
                        $request->input('p_fecha_stock') ?: null,
                        false,
                    ]
                );

                $mensaje .= $resultado->ok
                    ? ' — Pipeline TXD ejecutado OK.'
                    : ' — El pipeline TXD falló, revisar automatizacion_alertas.';
            } catch (Throwable $e) {
                Log::error('Error ejecutando automatizacion_ejecutar_txd_completo: ' . $e->getMessage());
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json(['status' => $mensaje, 'message' => 'Staging cargado, pero el pipeline falló: ' . $e->getMessage()], 500);
                }
                return back()->with('status', $mensaje)
                    ->withErrors(['pipeline' => 'Staging cargado, pero el pipeline falló: ' . $e->getMessage()]);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => $mensaje, 'message' => $mensaje, 'resumen' => $resumen]);
        }
        return back()->with('status', $mensaje);
    }

    public function pipeline(Request $request)
    {
        $request->validate([
            'p_fecha_stock' => ['nullable', 'date'],
        ]);
        try {
            $r = DB::selectOne('SELECT automatizacion_ejecutar_txd_completo(NULL, NULL, ?, ?) AS ok', [
                $request->input('p_fecha_stock') ?: null,
                false,
            ]);
            $msg = $r->ok ? 'Pipeline TXD ejecutado OK.' : 'Pipeline TXD falló, revisar automatizacion_alertas.';
            if ($request->expectsJson() || $request->ajax()) return response()->json(['ok' => (bool) $r->ok, 'message' => $msg]);
            return back()->with('status', $msg);
        } catch (Throwable $e) {
            Log::error('Error pipeline TXD: '.$e->getMessage());
            if ($request->expectsJson() || $request->ajax()) return response()->json(['message' => 'Error pipeline: '.$e->getMessage()], 500);
            return back()->withErrors(['pipeline' => 'Error pipeline: '.$e->getMessage()]);
        }
    }
}
