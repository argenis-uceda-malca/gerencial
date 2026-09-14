<?php

namespace App\Services\Captura;

use Illuminate\Database\ConnectionInterface;

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
    public function insertar(ConnectionInterface $conexion, array $documento, string $pfx): void
    {
        // Sin transaction(): los linked servers requieren MSDTC para
        // transacciones distribuidas. Si no está habilitado en el servidor
        // central, el BEGIN TRANSACTION falla. Cada INSERT es atómico de
        // forma implícita en el servidor remoto.
        // Detalle primero: Bizlinks monitorea SPE_EINVOICEHEADER con polling
        // muy rápido (< 2s). Si insertamos header antes que el detalle,
        // Bizlinks valida y reporta "no items" antes de que los ítems existan.
        foreach ($documento['detalle'] as $item) {
            $this->insertarDetalleItem($conexion, $item, $pfx);
        }

        $this->insertarCabecera($conexion, $documento['cabecera'], $pfx);
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
        $columnas      = implode(', ', array_map(fn ($c) => "[{$c}]", array_keys($datos)));
        $placeholders  = implode(', ', array_fill(0, count($datos), '?'));

        $conexion->statement(
            "INSERT INTO {$tabla} ({$columnas}) VALUES ({$placeholders})",
            array_values($datos)
        );
    }
}
