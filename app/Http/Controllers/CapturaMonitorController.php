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

class CapturaMonitorController extends Controller
{
    public function index()
    {
        $tiendas = FeTienda::orderBy('codigo_tienda')->get();

        $stats = [
            'capturados_hoy' => FeControlRegistro::whereDate('fecha_captura', today())->count(),
            'errores'        => FeControlRegistro::where('estado', 'ERROR_CAPTURA')->count(),
            'cuarentena'     => FeControlRegistro::where('estado', 'CUARENTENA')->count(),
            'pendientes'     => FeControlRegistro::where('estado', 'PENDIENTE')->count(),
        ];

        $logsRecientes = FeLogSistema::orderByDesc('fecha')->take(50)->get();

        return view('captura.monitor', compact('tiendas', 'stats', 'logsRecientes'));
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

                $resultado[$tienda->codigo_tienda] = ['cola' => (int) $row->total, 'ok' => true];
            } catch (\Throwable $e) {
                $resultado[$tienda->codigo_tienda] = ['cola' => null, 'ok' => false];
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

        $total    = $query->count();
        $start    = (int) $request->get('start', 0);
        $length   = (int) $request->get('length', 25);

        $registros = $query->orderByDesc('fecha_ultima_actualizacion')
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
                'estado'                  => "<span class=\"badge bg-{$badge}\">{$r->estado}</span>",
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
        $registro->update(['estado' => 'PENDIENTE', 'motivo_cuarentena' => null]);

        return response()->json(['ok' => true]);
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
                "SELECT TOP 1 bl_url_pdf, bl_url_cdr, bl_url_ubl, bl_mensajeSunat, bl_estadoRegistro, bl_fechaRespuestaSunat
                 FROM {$pfx}[SPE_EINVOICE_RESPONSE] WHERE [serieNumero] = ?",
                [$registro->serie_numero_bizlinks]
            );

            return response()->json([
                'ok'       => true,
                'header'   => (array) $header,
                'detalles' => array_map(function ($d) { return (array) $d; }, $detalles),
                'archivos' => $response ? [
                    'url_pdf' => $response->bl_url_pdf,
                    'url_cdr' => $response->bl_url_cdr,
                    'url_ubl' => $response->bl_url_ubl,
                    'mensaje_sunat'  => $response->bl_mensajeSunat,
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

    // Ejecutar el motor manualmente (fire-and-forget con timeout largo)
    public function ejecutar()
    {
        try {
            Artisan::call('captura:ejecutar');
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
            $btns .= "<button class=\"btn btn-sm btn-outline-primary btn-ver-cpe me-1\" data-id=\"{$r->id}\" title=\"Ver CPE en Bizlinks\"><i class=\"bx bx-file\"></i></button>";
        }
        if (in_array($r->estado, ['ERROR_CAPTURA', 'CUARENTENA'], true)) {
            $btns .= "<button class=\"btn btn-sm btn-outline-warning btn-reset me-1\" data-id=\"{$r->id}\" title=\"Reintentar\"><i class=\"bx bx-refresh\"></i></button>";
        }
        if (in_array($r->estado, ['PENDIENTE', 'ERROR_CAPTURA'], true)) {
            $btns .= "<button class=\"btn btn-sm btn-outline-danger btn-forzar-directo me-1\" data-id=\"{$r->id}\" title=\"Forzar captura ahora\"><i class=\"bx bx-send\"></i></button>";
        }
        $btns .= "<button class=\"btn btn-sm btn-outline-secondary btn-errores\" data-id=\"{$r->id}\" title=\"Ver errores\"><i class=\"bx bx-info-circle\"></i></button>";
        return $btns;
    }
}
