<?php

namespace App\Http\Controllers;

use App\Models\FeControlRegistro;
use App\Models\FeHistorialError;
use App\Models\FeLogSistema;
use App\Models\FeTienda;
use App\Services\Captura\ConexionTiendaService;
use App\Services\Captura\MotorCapturaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class CapturaMonitorController extends Controller
{
    public function index()
    {
        // Cards: todas las tiendas, activas primero luego inactivas, ordenado por código
        $tiendas = FeTienda::orderByRaw("CASE WHEN estado='ACTIVA' THEN 0 ELSE 1 END")
            ->orderBy('codigo_tienda')
            ->get();

        // Solo para el modal de ejecución del motor
        $tiendasActivas = $tiendas->where('estado', 'ACTIVA')->values();

        $stats = [
            'capturados_hoy' => FeControlRegistro::whereDate('fecha_captura', today())->count(),
            'errores'        => FeControlRegistro::where('estado', 'ERROR_CAPTURA')->count(),
            'cuarentena'     => FeControlRegistro::where('estado', 'CUARENTENA')->count(),
            'pendientes'     => FeControlRegistro::where('estado', 'PENDIENTE')->count(),
        ];

        $logsRecientes = FeLogSistema::orderByDesc('fecha')->take(50)->get();

        return view('captura.monitor', compact('tiendas', 'tiendasActivas', 'stats', 'logsRecientes'));
    }

    // AJAX — cola pendiente por tienda (COUNT en Soluflex)
    public function colaPorTienda(ConexionTiendaService $conexionSvc)
    {
        $tiendas = FeTienda::where('estado', 'ACTIVA')->orderBy('codigo_tienda')->get();

        $resultado = [];
        foreach ($tiendas as $tienda) {
            try {
                $conexion = $conexionSvc->conexionSoluflex($tienda);
                $pfx      = $conexionSvc->prefijoSoluflex($tienda);
                $cursor   = $tienda->ultimo_idtransaccion_capturado ?? 0;

                if ($tienda->tipo_fuente === 'CENTRAL') {
                    $fechaInicio = $tienda->fecha_inicio_captura?->format('Ymd');
                    $filtroFecha = $fechaInicio ? "AND c.FECHA_DOCUMENTO >= '{$fechaInicio}'" : '';
                    $row = $conexion->selectOne("
                        SELECT COUNT(*) AS total
                        FROM {$pfx}[CABECERA_DOCUMENTO] c
                        INNER JOIN {$pfx}[DOCUMENTOS] d
                            ON d.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
                        INNER JOIN {$pfx}[DOCUMENTOS_SERIES] ds
                            ON ds.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
                           AND ds.IDEMPRESA        = c.IDEMPRESA
                           AND ds.NUMERO_SERIE     = c.NUMERO_SERIE
                        WHERE c.CODIGO_ESTADO        = '12'
                          AND d.FLAG_FACT_ELECTRONICA = 'S'
                          AND ds.FLAG_ELECTRONICO     = 'S'
                          AND c.IDSUCURSAL NOT IN (SELECT IDSUCURSAL FROM {$pfx}[iptiendas])
                          {$filtroFecha}
                    ");
                    // Descontar los ya capturados en nuestro sistema
                    $yaCapturados = FeControlRegistro::where('codigo_tienda', 'CENTRAL')
                        ->whereIn('estado', ['CAPTURADO', 'PENDIENTE', 'CUARENTENA'])
                        ->count();
                    $cola = max(0, (int) $row->total - $yaCapturados);
                } else {
                    $row = $conexion->selectOne("
                        SELECT COUNT(*) AS total
                        FROM {$pfx}[CABECERA_DOCUMENTO] c
                        INNER JOIN {$pfx}[DOCUMENTOS] d
                            ON d.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
                        INNER JOIN {$pfx}[DOCUMENTOS_SERIES] ds
                            ON ds.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
                           AND ds.IDEMPRESA        = c.IDEMPRESA
                           AND ds.NUMERO_SERIE     = c.NUMERO_SERIE
                        WHERE c.IDTRANSACCION > ?
                          AND c.CODIGO_ESTADO      = '12'
                          AND d.FLAG_FACT_ELECTRONICA = 'S'
                          AND ds.FLAG_ELECTRONICO  = 'S'
                    ", [$cursor]);
                    $cola = (int) $row->total;
                }

                $resultado[$tienda->codigo_tienda] = ['cola' => $cola, 'ok' => true];
            } catch (\Throwable $e) {
                \Log::error("colaPorTienda [{$tienda->codigo_tienda}]: " . $e->getMessage());
                $resultado[$tienda->codigo_tienda] = ['cola' => null, 'ok' => false, 'error' => $e->getMessage()];
            } finally {
                try { $conexionSvc->cerrar($tienda, 'soluflex'); } catch (\Throwable $e) {}
            }
        }

        return response()->json($resultado);
    }

    // AJAX — tabla de registros con paginación server-side
    public function registros(Request $request)
    {
        $query = FeControlRegistro::with('tienda');

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }
        if ($request->filled('tienda')) {
            $query->where('codigo_tienda', $request->tienda);
        }
        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('serie_numero_bizlinks', 'ilike', "%{$b}%")
                  ->orWhere('numero_documento_cliente', 'ilike', "%{$b}%")
                  ->orWhere('razon_social_cliente', 'ilike', "%{$b}%");
            });
        }
        if ($request->filled('fecha_ini')) {
            $query->whereDate('fecha_venta', '>=', $request->fecha_ini);
        }
        if ($request->filled('fecha_fin')) {
            $query->whereDate('fecha_venta', '<=', $request->fecha_fin);
        }

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);

        $registros = $query->orderByDesc('id')
            ->skip($start)->take($length)->get();

        $estadoBadge = [
            'CAPTURADO'     => 'success',
            'PENDIENTE'     => 'secondary',
            'ERROR_CAPTURA' => 'danger',
            'CUARENTENA'    => 'warning',
        ];

        $data = $registros->map(function ($r) use ($estadoBadge) {
            $badge = $estadoBadge[$r->estado] ?? 'secondary';
            return [
                'id'                      => $r->id,
                'codigo_tienda'           => $r->codigo_tienda,
                'idtransaccion_soluflex'  => $r->idtransaccion_soluflex,
                'serie_numero_bizlinks'   => $r->serie_numero_bizlinks ?? '—',
                'tipo_documento_sunat'    => $r->tipo_documento_sunat ?? '—',
                'fecha_venta'             => ($r->fecha_venta !== null ? $r->fecha_venta->format('Y-m-d') : null) ?? '—',
                'importe_total'           => number_format((float) $r->importe_total, 2),
                'razon_social_cliente'    => $r->razon_social_cliente ?? '—',
                'numero_documento_cliente'=> $r->numero_documento_cliente ?? '—',
                'estado'                  => $r->estado,
                'estado_bizlinks'         => $r->estado_bizlinks ?? '—',
                'codigo_error_bizlinks'   => $r->codigo_error_bizlinks ?? '—',
                'mensaje_bizlinks'        => $r->mensaje_bizlinks ?? '',
                'intentos_captura'        => $r->intentos_captura,
                'fecha_captura'           => ($r->fecha_captura !== null ? $r->fecha_captura->format('Y-m-d H:i') : null) ?? '—',
                'acciones'                => $this->botonesAccion($r),
            ];
        });

        return response()->json([
            'draw'            => (int) $request->get('draw', 1),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    // AJAX — detalle de errores de un registro
    public function erroresRegistro(int $id)
    {
        $errores = FeHistorialError::where('id_control_registro', $id)
            ->orderByDesc('id')->get(['id', 'codigo_error', 'mensaje_original', 'resuelto', 'fecha_error']);

        return response()->json($errores);
    }

    // Resetear un registro a PENDIENTE para que el motor lo reintente
    public function resetRegistro(int $id)
    {
        $registro = FeControlRegistro::findOrFail($id);
        $registro->update([
            'estado'                => 'PENDIENTE',
            'motivo_cuarentena'     => null,
            'intentos_captura'      => 0,
            'estado_bizlinks'       => null,
            'codigo_error_bizlinks' => null,
            'mensaje_bizlinks'      => null,
            'fecha_sync_bizlinks'   => null,
        ]);

        return response()->json(['ok' => true]);
    }

    // Reset masivo: errores/cuarentena o rechazados por Bizlinks/SUNAT
    public function resetMasivo(Request $request)
    {
        if ($request->input('tipo') === 'bizlinks_rechazados') {
            // Reintentar documentos rechazados por SUNAT (R) — los re-inserta en Bizlinks
            $query = FeControlRegistro::where('estado', 'CAPTURADO')
                ->where('estado_bizlinks', 'R');

            if ($request->filled('codigo_tienda')) {
                $query->where('codigo_tienda', $request->codigo_tienda);
            }

            $total = $query->count();
            $query->update([
                'estado'                => 'ERROR_CAPTURA',
                'estado_bizlinks'       => null,
                'codigo_error_bizlinks' => null,
                'mensaje_bizlinks'      => null,
                'fecha_sync_bizlinks'   => null,
            ]);
        } else {
            $query = FeControlRegistro::whereIn('estado', ['ERROR_CAPTURA', 'CUARENTENA']);

            if ($request->filled('codigo_tienda')) {
                $query->where('codigo_tienda', $request->codigo_tienda);
            }

            $total = $query->count();
            $query->update([
                'estado'            => 'PENDIENTE',
                'motivo_cuarentena' => null,
                'intentos_captura'  => 0,
            ]);
        }

        return response()->json(['ok' => true, 'reseteados' => $total]);
    }

    // Forzar captura directamente desde un ID de fe_control_registros (sin que el usuario sepa el IDTRANSACCION)
    public function forzarRegistro(int $id, MotorCapturaService $motor)
    {
        $registro = FeControlRegistro::with('tienda')->findOrFail($id);
        $tienda   = $registro->tienda;
        $result   = $motor->forzarDocumento($tienda, (int) $registro->idtransaccion_soluflex);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    // Forzar captura de un documento específico por IDTRANSACCION
    public function forzarCaptura(Request $request, MotorCapturaService $motor)
    {
        $request->validate([
            'codigo_tienda'   => 'required|string',
            'idtransaccion'   => 'required|integer|min:1',
        ]);

        $tienda = FeTienda::findOrFail($request->codigo_tienda);
        $result = $motor->forzarDocumento($tienda, (int) $request->idtransaccion);

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    // Ver detalles del documento CPE en Bizlinks (para modal de descarga)
    public function verDocumento(int $id, ConexionTiendaService $conexionSvc)
    {
        $registro = FeControlRegistro::with('tienda')->findOrFail($id);

        if ($registro->estado !== 'CAPTURADO' || !$registro->serie_numero_bizlinks) {
            return response()->json(['ok' => false, 'error' => 'Documento no capturado en Bizlinks todavía.'], 422);
        }

        $tienda = $registro->tienda;
        try {
            $conexion = $conexionSvc->conexionBizlinks($tienda);
            $pfx      = $conexionSvc->prefijoBizlinks($tienda);

            $header = $conexion->selectOne(
                "SELECT TOP 1 * FROM {$pfx}[SPE_EINVOICEHEADER] WHERE [serieNumero] = ?",
                [$registro->serie_numero_bizlinks]
            );

            if (!$header) {
                return response()->json(['ok' => false, 'error' => 'No se encontró el comprobante en Bizlinks.'], 404);
            }

            $detalles = $conexion->select(
                "SELECT * FROM {$pfx}[SPE_EINVOICEDETAIL] WHERE [serieNumero] = ?",
                [$registro->serie_numero_bizlinks]
            );

            $response = $conexion->selectOne(
                "SELECT TOP 1 bl_url_pdf, bl_url_cdr, bl_url_ubl, bl_mensaje, bl_mensajeSunat, bl_estadoRegistro, bl_fechaRespuestaSunat
                 FROM {$pfx}[SPE_EINVOICE_RESPONSE] WHERE [serieNumero] = ?",
                [$registro->serie_numero_bizlinks]
            );

            return response()->json([
                'ok'       => true,
                'header'   => (array) $header,
                'detalles' => array_map(function ($d) { return (array) $d; }, $detalles),
                'archivos' => $response ? [
                    'url_pdf'        => $response->bl_url_pdf,
                    'url_cdr'        => $response->bl_url_cdr,
                    'url_ubl'        => $response->bl_url_ubl,
                    'mensaje_sunat'  => $response->bl_mensajeSunat ?: $response->bl_mensaje,
                    'estado'         => $response->bl_estadoRegistro,
                    'fecha_respuesta'=> $response->bl_fechaRespuestaSunat,
                ] : null,
                'registro' => [
                    'id'           => $registro->id,
                    'serie_numero' => $registro->serie_numero_bizlinks,
                    'tipo'         => $registro->tipo_documento_sunat,
                    'fecha_venta'  => $registro->fecha_venta !== null ? $registro->fecha_venta->format('d/m/Y') : null,
                    'importe'      => number_format((float) $registro->importe_total, 2),
                    'cliente'      => $registro->razon_social_cliente,
                    'ruc_dni'      => $registro->numero_documento_cliente,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        } finally {
            try { $conexionSvc->cerrar($tienda, 'bizlinks'); } catch (\Throwable $e) {}
        }
    }

    // Sincronizar estado de Bizlinks para todos los CAPTURADO sin estado final
    public function syncBizlinks(Request $request)
    {
        try {
            $params = $request->filled('codigo_tienda')
                ? ['--tienda' => $request->codigo_tienda]
                : [];
            Artisan::call('captura:monitorear-bizlinks', $params);
            $output = Artisan::output();
            return response()->json(['ok' => true, 'output' => trim($output) ?: 'Sincronización completada']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    // Estado del motor (running / idle) — consultado por el frontend cada pocos segundos
    public function estadoMotor()
    {
        $desde = Cache::get('captura:motor:corriendo');
        return response()->json([
            'corriendo' => $desde !== null,
            'desde'     => $desde,
        ]);
    }

    // Ejecutar el motor manualmente (fire-and-forget con timeout largo)
    public function ejecutar(Request $request)
    {
        if (Cache::has('captura:motor:corriendo')) {
            return response()->json(['ok' => false, 'error' => 'El motor ya está corriendo.'], 409);
        }

        try {
            $params = [];
            $tiendas = $request->input('tiendas', []);
            if (is_array($tiendas) && count($tiendas)) {
                $params['--tiendas'] = implode(',', array_filter($tiendas));
            } elseif ($request->filled('codigo_tienda')) {
                $params['--tiendas'] = $request->codigo_tienda;
            }
            Artisan::call('captura:ejecutar', $params);
            $output = Artisan::output();
            return response()->json(['ok' => true, 'output' => trim($output)]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function botonesAccion(FeControlRegistro $r): string
    {
        $btns = '';
        if ($r->estado === 'CAPTURADO' && $r->serie_numero_bizlinks) {
            $btns .= "<button class=\"ac-btn ac-btn-cpe btn-ver-cpe\" data-id=\"{$r->id}\" title=\"Ver CPE en Bizlinks\"><i class=\"bx bx-file-blank\"></i></button>";
        }
        if (in_array($r->estado, ['ERROR_CAPTURA', 'CUARENTENA'], true)
            || ($r->estado === 'CAPTURADO' && in_array($r->estado_bizlinks, ['E', 'L'], true))) {
            $btns .= "<button class=\"ac-btn ac-btn-retry btn-reset\" data-id=\"{$r->id}\" title=\"Reintentar en Bizlinks\"><i class=\"bx bx-refresh\"></i></button>";
        }
        if (in_array($r->estado, ['PENDIENTE', 'ERROR_CAPTURA'], true)) {
            $btns .= "<button class=\"ac-btn ac-btn-force btn-forzar-directo\" data-id=\"{$r->id}\" title=\"Forzar captura ahora\"><i class=\"bx bx-send\"></i></button>";
        }
        $btns .= "<button class=\"ac-btn ac-btn-info btn-errores\" data-id=\"{$r->id}\" title=\"Ver historial de errores\"><i class=\"bx bx-info-circle\"></i></button>";
        return $btns;
    }
}
