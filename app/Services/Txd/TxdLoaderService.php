<?php

namespace App\Services\Txd;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Carga los datos ya parseados/normalizados en las tablas automatizacion_temp_*.
 *
 * A diferencia del script Python (que usa COPY sin lista de columnas, es decir,
 * inserta por POSICIÓN), aquí se inserta siempre por NOMBRE de columna explícito.
 * Esto es intencional: evita que un cambio de orden de columnas en la tabla rompa
 * silenciosamente el mapeo de datos.
 *
 * Cada tabla se TRUNCATE antes de insertar (igual que el manual original:
 * "CORRER SIEMPRE ESTOS 4 TRUNCATE"), porque el staging representa la carga
 * de la semana en curso, no un histórico acumulado.
 */
class TxdLoaderService
{
    const CHUNK_SIZE = 1000;

    public function loadOechsle(Collection $rows): int
    {
        return $this->truncateAndInsert('automatizacion_temp_oechsle_txd', $rows);
    }

    public function loadRipley(Collection $rows): int
    {
        return $this->truncateAndInsert('automatizacion_temp_ripley_txd', $rows);
    }

    public function loadFalabellaStock(Collection $rows): int
    {
        return $this->truncateAndInsert('automatizacion_stock_txd', $rows);
    }

    public function loadFalabellaVentas(Collection $rows): int
    {
        return $this->truncateAndInsert('automatizacion_temp_saga_txd', $rows);
    }

    private function truncateAndInsert(string $table, Collection $rows): int
    {
        DB::table($table)->truncate();

        if ($rows->isEmpty()) {
            return 0;
        }

        $inserted = 0;
        foreach ($rows->chunk(self::CHUNK_SIZE) as $chunk) {
            DB::table($table)->insert(
                $chunk->map(fn ($item) => (array) $item)->toArray()
            );
            $inserted += $chunk->count();
        }

        return $inserted;
    }
}
