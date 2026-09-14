<?php

namespace App\Services\Captura;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;

/**
 * Consulta las ventas candidatas a captura en SOLUFLEX_FARO: cerradas
 * (CODIGO_ESTADO = '12'), de un tipo de documento marcado como electronico,
 * con la serie especifica habilitada para electronico, y posteriores al
 * cursor de la tienda.
 *
 * $pfx es el prefijo de 4 partes para modo gateway, ej.:
 *   "[10.20.0.15].[SOLUFLEX_FARO].[dbo]."
 * En modo directo es cadena vacia y las tablas se usan sin prefijo.
 */
class DetectorVentasService
{
    public function obtenerVentasNuevas(ConnectionInterface $conexion, ?int $ultimoIdCapturado, string $pfx): Collection
    {
        // NULL significa que la tienda nunca capturo nada: arrancar desde 0.
        $cursor = $ultimoIdCapturado ?? 0;

        return collect($conexion->select("
            SELECT c.*
            FROM {$pfx}[CABECERA_DOCUMENTO] c
            INNER JOIN {$pfx}[DOCUMENTOS] d
                ON d.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
            INNER JOIN {$pfx}[DOCUMENTOS_SERIES] ds
                ON ds.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
               AND ds.IDEMPRESA = c.IDEMPRESA
               AND ds.NUMERO_SERIE = c.NUMERO_SERIE
            WHERE c.IDTRANSACCION > ?
              AND c.CODIGO_ESTADO = '12'
              AND d.FLAG_FACT_ELECTRONICA = 'S'
              AND ds.FLAG_ELECTRONICO = 'S'
            ORDER BY c.IDTRANSACCION ASC
        ", [$cursor]));
    }

    public function obtenerVentaPorId(ConnectionInterface $conexion, int $idTransaccion, string $pfx): ?object
    {
        $row = $conexion->selectOne("
            SELECT c.*
            FROM {$pfx}[CABECERA_DOCUMENTO] c
            INNER JOIN {$pfx}[DOCUMENTOS] d
                ON d.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
            INNER JOIN {$pfx}[DOCUMENTOS_SERIES] ds
                ON ds.CODIGO_DOCUMENTO = c.CODIGO_DOCUMENTO
               AND ds.IDEMPRESA = c.IDEMPRESA
               AND ds.NUMERO_SERIE = c.NUMERO_SERIE
            WHERE c.IDTRANSACCION = ?
              AND c.CODIGO_ESTADO = '12'
              AND d.FLAG_FACT_ELECTRONICA = 'S'
              AND ds.FLAG_ELECTRONICO = 'S'
        ", [$idTransaccion]);

        return $row ?: null;
    }

    public function obtenerDetalle(ConnectionInterface $conexion, int $idTransaccion, string $pfx): Collection
    {
        return collect($conexion->select("
            SELECT *
            FROM {$pfx}[DETALLE_DOCUMENTO]
            WHERE IDTRANSACCION = ?
            ORDER BY SECUENCIA ASC
        ", [$idTransaccion]));
    }
}
