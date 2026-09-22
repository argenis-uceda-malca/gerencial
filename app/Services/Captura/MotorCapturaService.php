<?php

namespace App\Services\Captura;

use App\Models\FeControlRegistro;
use App\Models\FeLogSistema;
use App\Models\FeTienda;
use Illuminate\Database\ConnectionInterface;
use Throwable;

/**
 * Orquesta la captura completa de una tienda: detecta ventas nuevas,
 * las transforma, valida, e inserta en Bizlinks. Ver Diseno de Flujo
 * del Motor de Captura para el algoritmo completo documentado.
 */
class MotorCapturaService
{
    /** @var ConexionTiendaService */
    private $conexiones;
    /** @var DetectorVentasService */
    private $detector;
    /** @var TransformadorDocumentoService */
    private $transformador;
    /** @var ValidadorCapturaService */
    private $validador;
    /** @var InsercionBizlinksService */
    private $insercion;

    public function __construct(
        ConexionTiendaService $conexiones,
        DetectorVentasService $detector,
        TransformadorDocumentoService $transformador,
        ValidadorCapturaService $validador,
        InsercionBizlinksService $insercion
    ) {
        $this->conexiones   = $conexiones;
        $this->detector     = $detector;
        $this->transformador = $transformador;
        $this->validador    = $validador;
        $this->insercion    = $insercion;
    }

    /**
     * Procesa todas las ventas nuevas de una tienda. Aisla cualquier
     * excepcion para que un fallo en una tienda no afecte a las demas.
     *
     * @return array{capturados: int, cuarentena: int, errores: int}
     */
    public function procesarTienda(FeTienda $tienda): array
    {
        $resumen = ['capturados' => 0, 'cuarentena' => 0, 'errores' => 0];

        try {
            $conexionSoluflex = $this->conexiones->conexionSoluflex($tienda);
            $pfxSoluflex      = $this->conexiones->prefijoSoluflex($tienda);

            $ventas = $this->detector->obtenerVentasNuevas(
                $conexionSoluflex,
                $tienda->ultimo_idtransaccion_capturado,
                $pfxSoluflex
            );

            foreach ($ventas as $venta) {
                $this->procesarVenta($tienda, $conexionSoluflex, $pfxSoluflex, $venta, $resumen);

                // El cursor avanza venta por venta, no al final del lote,
                // para no reprocesar ventas ya resueltas si el proceso
                // se interrumpe a mitad de camino (ver seccion 5 del
                // Diseno de Flujo).
                $tienda->ultimo_idtransaccion_capturado = (int) $venta->IDTRANSACCION;
                $tienda->save();
            }

            $tienda->fecha_ultima_captura = now();
            $tienda->save();

            FeLogSistema::log(
                'INFO',
                'MOTOR_CAPTURA',
                "Tienda {$tienda->codigo_tienda}: capturados={$resumen['capturados']}, cuarentena={$resumen['cuarentena']}, errores={$resumen['errores']}",
                $tienda->codigo_tienda
            );
        } catch (Throwable $e) {
            FeLogSistema::log(
                'ERROR',
                'MOTOR_CAPTURA',
                "Fallo procesando tienda {$tienda->codigo_tienda}: {$e->getMessage()}",
                $tienda->codigo_tienda
            );
        } finally {
            $this->conexiones->cerrar($tienda, 'soluflex');
            $this->conexiones->cerrar($tienda, 'bizlinks');
        }

        return $resumen;
    }

    /**
     * Fuerza la captura de un documento específico por IDTRANSACCION,
     * ignorando el cursor de la tienda. Útil para documentos que quedaron
     * atrás del cursor por un problema puntual.
     *
     * @return array{ok: bool, mensaje: string, estado: string}
     */
    public function forzarDocumento(FeTienda $tienda, int $idTransaccion): array
    {
        try {
            $conexionSoluflex = $this->conexiones->conexionSoluflex($tienda);
            $pfxSoluflex      = $this->conexiones->prefijoSoluflex($tienda);

            $venta = $this->detector->obtenerVentaPorId($conexionSoluflex, $idTransaccion, $pfxSoluflex);

            if (! $venta) {
                return ['ok' => false, 'mensaje' => "IDTRANSACCION {$idTransaccion} no encontrado en Soluflex (debe estar cerrado con CODIGO_ESTADO=12 y FLAG_FACT_ELECTRONICA=S).", 'estado' => ''];
            }

            $resumen = ['capturados' => 0, 'cuarentena' => 0, 'errores' => 0];
            $this->procesarVenta($tienda, $conexionSoluflex, $pfxSoluflex, $venta, $resumen);

            FeLogSistema::log(
                'INFO',
                'FORZAR_CAPTURA',
                "Forzado IDTRANSACCION={$idTransaccion} tienda={$tienda->codigo_tienda}: " . json_encode($resumen),
                $tienda->codigo_tienda
            );

            if ($resumen['capturados'] > 0) {
                return ['ok' => true, 'mensaje' => 'Documento capturado correctamente.', 'estado' => 'CAPTURADO'];
            }
            if ($resumen['cuarentena'] > 0) {
                return ['ok' => false, 'mensaje' => 'El documento fue a cuarentena por errores de validación.', 'estado' => 'CUARENTENA'];
            }
            return ['ok' => false, 'mensaje' => 'Error al insertar en Bizlinks. Revisá la tabla de errores.', 'estado' => 'ERROR_CAPTURA'];
        } catch (Throwable $e) {
            FeLogSistema::log('ERROR', 'FORZAR_CAPTURA', $e->getMessage(), $tienda->codigo_tienda);
            return ['ok' => false, 'mensaje' => $e->getMessage(), 'estado' => 'ERROR'];
        } finally {
            try { $this->conexiones->cerrar($tienda, 'soluflex'); } catch (Throwable $e) {}
            try { $this->conexiones->cerrar($tienda, 'bizlinks'); } catch (Throwable $e) {}
        }
    }

    private function procesarVenta(FeTienda $tienda, ConnectionInterface $conexionSoluflex, string $pfxSoluflex, object $venta, array &$resumen): void
    {
        // La reserva del registro ocurre ANTES de tocar Bizlinks. La
        // restriccion UNIQUE (codigo_tienda, idtransaccion_soluflex) es
        // la que garantiza que esta venta nunca se procese dos veces,
        // incluso si el proceso se interrumpe a mitad de camino.
        $registro = FeControlRegistro::firstOrCreate(
            [
                'codigo_tienda'          => $tienda->codigo_tienda,
                'idtransaccion_soluflex' => $venta->IDTRANSACCION,
            ],
            [
                'estado'         => 'PENDIENTE',
                'fecha_venta'    => $venta->FECHA_DOCUMENTO,
                'importe_total'  => $venta->IMPORTE_TOTAL,
            ]
        );

        if (! in_array($registro->estado, ['PENDIENTE', 'ERROR_CAPTURA'], true)) {
            return;
        }

        $detalle   = $this->detector->obtenerDetalle($conexionSoluflex, (int) $venta->IDTRANSACCION, $pfxSoluflex);
        $documento = $this->transformador->transformar($conexionSoluflex, $tienda, $venta, $detalle, $pfxSoluflex);
        $errores   = $this->validador->validar($documento);

        if (! empty($errores)) {
            $continuar = $this->validador->resolver($registro, $documento, $errores);
            if (! $continuar) {
                $resumen['cuarentena']++;

                return;
            }
        }

        try {
            $this->insertarYConfirmar($tienda, $registro, $documento);
            $resumen['capturados']++;
        } catch (Throwable $e) {
            $this->marcarErrorCaptura($registro, $e->getMessage());
            $resumen['errores']++;
        }
    }

    private function insertarYConfirmar(FeTienda $tienda, FeControlRegistro $registro, array $documento): void
    {
        $pfxBizlinks      = $this->conexiones->prefijoBizlinks($tienda);
        $conexionBizlinks = $this->conexiones->conexionBizlinks($tienda);

        $this->insercion->insertar($conexionBizlinks, $documento, $pfxBizlinks);

        $estadoAnterior = $registro->estado;
        $registro->fill([
            'estado'                    => 'CAPTURADO',
            'serie_numero_bizlinks'     => $documento['cabecera']['serieNumero'],
            'tipo_documento_sunat'      => $documento['cabecera']['tipoDocumento'],
            'numero_documento_emisor'   => $documento['cabecera']['numeroDocumentoEmisor'],
            'numero_documento_cliente'  => $documento['cabecera']['numeroDocumentoAdquiriente'],
            'razon_social_cliente'      => $documento['cabecera']['razonSocialAdquiriente'],
            'fecha_captura'             => now(),
        ]);
        $registro->save();

        $registro->auditoria()->create([
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo'    => 'CAPTURADO',
            'origen'          => 'CAPTURA',
        ]);
    }

    private function marcarErrorCaptura(FeControlRegistro $registro, string $mensaje): void
    {
        $estadoAnterior = $registro->estado;
        $registro->estado = 'ERROR_CAPTURA';
        $registro->intentos_captura++;
        $registro->save();

        $registro->auditoria()->create([
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo'    => 'ERROR_CAPTURA',
            'origen'          => 'CAPTURA',
            // fe_auditoria_estados.detalle es VARCHAR(500); truncar para evitar error de BD.
            'detalle'         => mb_substr($mensaje, 0, 490),
        ]);

        $registro->errores()->create([
            'codigo_error'     => 'ERROR_INSERCION_BIZLINKS',
            'mensaje_original' => mb_substr($mensaje, 0, 1000),
            'resuelto'         => false,
        ]);
    }
}
