<?php

namespace App\Console\Commands;

use App\Models\FeControlRegistro;
use App\Models\FeTienda;
use App\Services\Captura\ConexionTiendaService;
use Illuminate\Console\Command;
use Throwable;

class MonitorearBizlinks extends Command
{
    protected $signature   = 'captura:monitorear-bizlinks {--tienda= : Código de tienda específica}';
    protected $description = 'Sincroniza el estado y errores de Bizlinks en fe_control_registros';

    public function handle(ConexionTiendaService $conexionSvc): int
    {
        $query = FeTienda::where('estado', 'ACTIVA');
        if ($this->option('tienda')) {
            $query->where('codigo_tienda', $this->option('tienda'));
        }

        foreach ($query->get() as $tienda) {
            $this->sincronizarTienda($tienda, $conexionSvc);
        }

        return 0;
    }

    private function sincronizarTienda(FeTienda $tienda, ConexionTiendaService $conexionSvc): void
    {
        $registros = FeControlRegistro::where('codigo_tienda', $tienda->codigo_tienda)
            ->where('estado', 'CAPTURADO')
            ->whereNotNull('serie_numero_bizlinks')
            ->where(function ($q) {
                // Solo estados no finales: NULL (pendiente) y L/E (en proceso/error transitorio).
                // 'A' y 'P' = aceptados; 'R' = rechazado definitivo — no re-sincronizar.
                // 'A' y 'P' = aceptados; 'R' = rechazado — estados finales, no re-sincronizar.
                $q->whereNull('estado_bizlinks')
                  ->orWhereIn('estado_bizlinks', ['L', 'E']);
            })
            ->where(function ($q) {
                // No re-sincronizar si ya se revisó hace menos de 5 minutos.
                $q->whereNull('fecha_sync_bizlinks')
                  ->orWhere('fecha_sync_bizlinks', '<', now()->subMinutes(5));
            })
            ->get();

        if ($registros->isEmpty()) {
            return;
        }

        try {
            $conexion = $conexionSvc->conexionBizlinks($tienda);
            $pfx      = $conexionSvc->prefijoBizlinks($tienda);

            // En vez de 2 queries por registro, traer todos los estados en 2 queries
            // totales usando IN por lotes de 500 (límite seguro de parámetros SQL Server).
            $actualizados = 0;
            foreach ($registros->chunk(500) as $lote) {
                $series = $lote->pluck('serie_numero_bizlinks')->all();
                $ph     = implode(',', array_fill(0, count($series), '?'));

                $responses = collect($conexion->select(
                    "SELECT [serieNumero], bl_estadoRegistro, bl_mensaje, bl_mensajeSunat,
                            bl_url_pdf, bl_url_cdr
                     FROM {$pfx}[SPE_EINVOICE_RESPONSE]
                     WHERE [serieNumero] IN ({$ph})",
                    $series
                ))->keyBy('serieNumero');

                $errorLogs = collect($conexion->select(
                    "SELECT SERIENUMERO, CODIGOERROR, DESCRIPCIONERROR
                     FROM {$pfx}[SPE_ERROR_LOG]
                     WHERE SERIENUMERO IN ({$ph})",
                    $series
                ))->keyBy('SERIENUMERO');

                $now = now();
                foreach ($lote as $reg) {
                    $serie    = $reg->serie_numero_bizlinks;
                    $response = $responses->get($serie);
                    $errorLog = $errorLogs->get($serie);

                    if ($response) {
                        // Bizlinks no siempre popula bl_estadoRegistro.
                        // Detectar aceptación también por PDF/CDR o por bl_mensaje con codigo=0.
                        $tieneArchivo    = ! empty($response->bl_url_pdf) || ! empty($response->bl_url_cdr);
                        $mensajeJson     = json_decode($response->bl_mensaje ?? '', true);
                        $aceptadoPorMsg  = isset($mensajeJson['codigo']) && $mensajeJson['codigo'] === '0';
                        $estado  = $response->bl_estadoRegistro ?: (($tieneArchivo || $aceptadoPorMsg) ? 'P' : 'L');
                        $mensaje = $response->bl_mensajeSunat ?: $response->bl_mensaje ?: null;
                        if ($errorLog && ! in_array($estado, ['A', 'P'])) {
                            $mensaje = $mensaje ?: "Error {$errorLog->CODIGOERROR}: {$errorLog->DESCRIPCIONERROR}";
                        }
                        $codigo = null;
                    } elseif ($errorLog) {
                        $estado  = 'E';
                        $mensaje = "Error {$errorLog->CODIGOERROR}: {$errorLog->DESCRIPCIONERROR}";
                        $codigo  = (string) $errorLog->CODIGOERROR;
                    } else {
                        continue;
                    }

                    $reg->update([
                        'estado_bizlinks'       => $estado,
                        'codigo_error_bizlinks'  => $codigo,
                        'mensaje_bizlinks'       => mb_substr($mensaje ?? '', 0, 500),
                        'fecha_sync_bizlinks'    => $now,
                    ]);
                    $actualizados++;
                }
            }

            $this->info("[{$tienda->codigo_tienda}] Sincronizados {$actualizados}/{$registros->count()} registros");
        } catch (Throwable $e) {
            $this->error("[{$tienda->codigo_tienda}] Sin conexión a Bizlinks: {$e->getMessage()}");
        } finally {
            try { $conexionSvc->cerrar($tienda, 'bizlinks'); } catch (Throwable $e) {}
        }
    }
}
