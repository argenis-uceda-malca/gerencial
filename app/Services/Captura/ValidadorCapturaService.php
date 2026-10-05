<?php

namespace App\Services\Captura;

use App\Models\FeControlRegistro;
use App\Models\FeNotificacion;
use App\Models\FeReglaValidacionCaptura;
use Illuminate\Support\Str;

/**
 * Valida el documento ya transformado y resuelve cada error encontrado
 * según la acción configurada en FE_REGLAS_VALIDACION_CAPTURA (ver
 * Diseño de Flujo del Motor de Captura, sección 6).
 */
class ValidadorCapturaService
{
    /** Tolerancia de redondeo entre cabecera y suma de detalle. */
    const TOLERANCIA_REDONDEO = 0.02;

    /**
     * @return array{errores: string[], mensajes: array<string,string>}
     *   errores  → lista de códigos encontrados
     *   mensajes → detalle legible por código, para el historial de errores
     */
    public function validar(array $documento): array
    {
        $errores   = [];
        $mensajes  = [];

        foreach ($documento['detalle'] as $item) {
            if (Str::of((string) $item['descripcion'])->trim()->isEmpty()) {
                $errores[]  = 'PRODUCTO_SIN_DESCRIPCION';
                $mensajes['PRODUCTO_SIN_DESCRIPCION'] = 'Ítem sin descripción de producto.';
                break;
            }
        }

        if (blank($documento['cabecera']['tipoDocumentoAdquiriente']) || blank($documento['cabecera']['numeroDocumentoAdquiriente'])) {
            $errores[]  = 'CLIENTE_SIN_DOCUMENTO';
            $mensajes['CLIENTE_SIN_DOCUMENTO'] = 'El comprobante no tiene documento de identidad del cliente.';
        }

        $sumaDetalle   = array_sum(array_column($documento['detalle'], 'importeTotalItem'));
        $totalCabecera = (float) $documento['cabecera']['totalVenta'];
        $diferencia    = round(abs($sumaDetalle - $totalCabecera), 4);
        if ($diferencia > self::TOLERANCIA_REDONDEO) {
            $errores[] = 'TOTALES_INCONSISTENTES';
            $mensajes['TOTALES_INCONSISTENTES'] = sprintf(
                'Suma líneas=%.2f, cabecera=%.2f, diferencia=%.4f (tolerancia=%.2f)',
                $sumaDetalle, $totalCabecera, $diferencia, self::TOLERANCIA_REDONDEO
            );
        }

        return [
            'errores'  => array_values(array_unique($errores)),
            'mensajes' => $mensajes,
        ];
    }

    /**
     * Aplica la acción configurada por cada error. Devuelve true si el
     * flujo debe continuar hacia la inserción en Bizlinks
     * (INSERTAR_CON_DEFAULT o ALERTA_SOLO), o false si el documento
     * debe quedar en cuarentena y NO insertarse.
     *
     * @param  array  $documento  Pasado por referencia: INSERTAR_CON_DEFAULT
     *                            puede completar campos faltantes.
     */
    public function resolver(FeControlRegistro $registro, array &$documento, array $errores, array $mensajes = []): bool
    {
        $continuar = true;

        foreach ($errores as $codigoError) {
            $regla = FeReglaValidacionCaptura::where('codigo_error', $codigoError)
                ->where('activo', true)
                ->first();

            // Sin regla configurada, CUARENTENA es el comportamiento por
            // defecto más seguro (ver Plan de Acción, sección 6).
            $accion = $regla->accion ?? 'CUARENTENA';

            $detalle = $mensajes[$codigoError] ?? $codigoError;
            $registro->errores()->create([
                'codigo_error'     => $codigoError,
                'mensaje_original' => $detalle,
                'resuelto'         => $accion !== 'CUARENTENA',
            ]);

            if ($accion === 'INSERTAR_CON_DEFAULT') {
                $this->aplicarDefault($documento, $codigoError, $regla->valor_default ?? null);
            } elseif ($accion === 'ALERTA_SOLO') {
                $this->generarAlerta($registro, $codigoError);
            } else {
                $continuar = false; // CUARENTENA o acción desconocida
            }
        }

        if (! $continuar) {
            $this->marcarCuarentena($registro, $errores);
        }

        return $continuar;
    }

    private function aplicarDefault(array &$documento, string $codigoError, ?string $valorDefault): void
    {
        if ($valorDefault === null) {
            return;
        }

        // TODO: agregar aquí el mapeo campo-a-campo para cada
        // codigo_error nuevo que se configure con INSERTAR_CON_DEFAULT.
        if ($codigoError === 'PRODUCTO_SIN_DESCRIPCION') {
            foreach ($documento['detalle'] as &$item) {
                if (blank($item['descripcion'])) {
                    $item['descripcion'] = $valorDefault;
                }
            }
        }
    }

    private function generarAlerta(FeControlRegistro $registro, string $codigoError): void
    {
        FeNotificacion::create([
            'id_control_registro' => $registro->id,
            'tipo' => 'ERROR_TECNICO',
            'destinatario' => config('captura.correo_alertas'),
            'asunto' => "Alerta de validación ({$codigoError}) - venta {$registro->idtransaccion_soluflex}",
            'estado_envio' => 'PENDIENTE',
        ]);
    }

    private function marcarCuarentena(FeControlRegistro $registro, array $errores): void
    {
        $estadoAnterior = $registro->estado;
        $registro->estado = 'CUARENTENA';
        $registro->motivo_cuarentena = implode(', ', $errores);
        $registro->save();

        $registro->auditoria()->create([
            'estado_anterior' => $estadoAnterior,
            'estado_nuevo' => 'CUARENTENA',
            'origen' => 'CAPTURA',
            'detalle' => $registro->motivo_cuarentena,
        ]);
    }
}
