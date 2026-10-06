<?php

namespace App\Services\Captura;

use Illuminate\Database\ConnectionInterface;

/**
 * Indica que el documento ya existe en Bizlinks (inserción parcial previa).
 * El motor lo trata como CAPTURADO, no como ERROR_CAPTURA.
 */
class DuplicadoEnBizlinksException extends \RuntimeException {}

/**
 * Inserta el documento ya transformado y validado en las tablas de la
 * base intermedia de Bizlinks (SPE_EINVOICEHEADER / SPE_EINVOICEDETAIL).
 *
 * $pfx es el prefijo de 4 partes para modo gateway, ej.:
 *   "[10.20.0.15].[BIZLINKS_TST21].[dbo]."
 * En modo directo es cadena vacia.
 *
 * Se usa INSERT con raw SQL para garantizar compatibilidad con linked
 * servers (el Query Builder de Laravel envolverla el nombre de la tabla
 * en corchetes extra y romperia la sintaxis de 4 partes).
 *
 * NOTA: si el servidor central no tiene DTC configurado, la transaccion
 * distribuida puede fallar. En ese caso retirar el $conexion->transaction()
 * y los inserts quedaran sin atomicidad (header + detalle por separado).
 *
 * IMPORTANTE: los nombres de columna asumen que coinciden 1 a 1 con las
 * claves devueltas por TransformadorDocumentoService. Confirmar contra
 * el DDL real de BIZLINKS_TST21 antes de la primera prueba real.
 */
class InsercionBizlinksService
{
    /**
     * Inserta el documento en Bizlinks. Si el header ya existe (inserción
     * parcial previa que dejó ERROR_CAPTURA sin limpiar), lanza
     * DuplicadoEnBizlinksException para que el motor lo trate como CAPTURADO
     * en lugar de reintentar y fallar de nuevo con PK violation.
     */
    public function insertar(ConnectionInterface $conexion, array $documento, string $pfx): void
    {
        $serie = $documento['cabecera']['serieNumero'];
        $ruc   = $documento['cabecera']['numeroDocumentoEmisor'];
        $tipo  = $documento['cabecera']['tipoDocumento'];

        // Limpiar registros anteriores si existen (reintento tras error en Bizlinks).
        // Incluye tablas de respuesta/error para que el monitor no lea el rechazo anterior.
        foreach ([
            "{$pfx}[SPE_EINVOICEDETAIL]",
            "{$pfx}[SPE_EINVOICEHEADER_ADD]",
            "{$pfx}[SPE_EINVOICEHEADER]",
        ] as $tabla) {
            $conexion->statement(
                "DELETE FROM {$tabla} WHERE [serieNumero] = ? AND [numeroDocumentoEmisor] = ? AND [tipoDocumento] = ?",
                [$serie, $ruc, $tipo]
            );
        }

        // SPE_EINVOICE_RESPONSE y SPE_ERROR_LOG: limpiar por serieNumero solamente
        // (esas tablas no siempre tienen numeroDocumentoEmisor / tipoDocumento).
        foreach ([
            "{$pfx}[SPE_EINVOICE_RESPONSE]",
            "{$pfx}[SPE_ERROR_LOG]",
        ] as $tabla) {
            try {
                $conexion->statement(
                    "DELETE FROM {$tabla} WHERE [serieNumero] = ?",
                    [$serie]
                );
            } catch (\Throwable $e) {
                // Ignorar si la tabla no existe o usa columna distinta
            }
        }

        // Detalle primero: Bizlinks monitorea SPE_EINVOICEHEADER con polling
        // muy rápido (< 2s). Si insertamos header antes que el detalle,
        // Bizlinks valida y reporta "no items" antes de que los ítems existan.
        try {
            foreach ($documento['detalle'] as $item) {
                $this->insertarDetalleItem($conexion, $item, $pfx);
            }

            foreach ($documento['headerAdd'] ?? [] as $addRow) {
                $this->insertarFila($conexion, "{$pfx}[SPE_EINVOICEHEADER_ADD]", $addRow);
            }

            $this->insertarCabecera($conexion, $documento['cabecera'], $pfx);
        } catch (\Throwable $e) {
            // Violación de PK (SQLSTATE 23000): el documento ya existe en Bizlinks
            // aunque fe_control_registros lo marca como ERROR_CAPTURA (captura parcial
            // previa donde el INSERT tuvo éxito pero el UPDATE de estado falló).
            // En ese caso lo tratamos como ya capturado, no como error real.
            if ($this->esDuplicadoPk($e)) {
                $yaExiste = $conexion->selectOne(
                    "SELECT TOP 1 [serieNumero] FROM {$pfx}[SPE_EINVOICEHEADER] WHERE [serieNumero] = ?",
                    [$serie]
                );
                if ($yaExiste) {
                    throw new DuplicadoEnBizlinksException("Documento {$serie} ya existe en Bizlinks (captura parcial previa).");
                }
            }
            throw $e;
        }
    }

    private function esDuplicadoPk(\Throwable $e): bool
    {
        $msg = $e->getMessage();
        // SQLSTATE 23000 = integrity constraint violation (PK, UNIQUE)
        return strpos($msg, '23000') !== false
            || strpos($msg, 'PRIMARY KEY') !== false
            || strpos($msg, 'duplicate key') !== false
            || strpos($msg, 'UNIQUE KEY') !== false;
    }

    private function insertarCabecera(ConnectionInterface $conexion, array $cabecera, string $pfx): void
    {
        $this->insertarFila($conexion, "{$pfx}[SPE_EINVOICEHEADER]", $cabecera);
    }

    private function insertarDetalleItem(ConnectionInterface $conexion, array $item, string $pfx): void
    {
        unset($item['importeTotalItem']);

        $this->insertarFila($conexion, "{$pfx}[SPE_EINVOICEDETAIL]", $item);
    }

    /**
     * Ejecuta un INSERT con SQL crudo para soportar nombres de tabla
     * con prefijo de 4 partes (linked servers).
     */
    private function insertarFila(ConnectionInterface $conexion, string $tabla, array $datos): void
    {
        $columnas      = implode(', ', array_map(function ($c) { return "[{$c}]"; }, array_keys($datos)));
        $placeholders  = implode(', ', array_fill(0, count($datos), '?'));

        $conexion->statement(
            "INSERT INTO {$tabla} ({$columnas}) VALUES ({$placeholders})",
            array_values($datos)
        );
    }
}
