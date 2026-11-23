<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\TbRetailService;

class GuardarConteosTbRetail extends Command
{
    protected $signature = 'tbretail:guardar-conteos
                            {fecha? : Y-m-d (default: ayer)}
                            {--desde= : Backfill: fecha inicial Y-m-d}
                            {--hasta= : Backfill: fecha final Y-m-d (default: ayer)}
                            {--tiendas= : idtienda separados por coma; limita el backfill a esas tiendas}';

    protected $description = 'Guarda los conteos de TB Retail (diario, o backfill por rango/tiendas)';

    public function handle(TbRetailService $tbRetailService)
    {
        $desde = $this->option('desde');

        // Modo diario (comportamiento original)
        if (!$desde) {
            $fecha = $this->argument('fecha');
            $tbRetailService->guardarConteosTbRetail('marca', $fecha);
            $tbRetailService->guardarConteosTbRetail('tienda', $fecha);
            $this->info('Conteos guardados correctamente.');
            return Command::SUCCESS;
        }

        // Modo backfill
        $ini = Carbon::parse($desde)->startOfDay();
        $fin = $this->option('hasta') ? Carbon::parse($this->option('hasta'))->startOfDay() : Carbon::yesterday();
        $tiendas = $this->option('tiendas')
            ? array_map('intval', explode(',', $this->option('tiendas')))
            : null;

        $this->info('Backfill ' . $ini->toDateString() . ' → ' . $fin->toDateString()
            . ($tiendas ? ' tiendas: ' . implode(',', $tiendas) : ' (todas)'));

        $fallidos = [];
        for ($d = $ini->copy(); $d->lte($fin); $d->addDay()) {
            $f = $d->toDateString();
            try {
                // Con tiendas puntuales solo se guarda tipo tienda; marca se recalcula al final.
                $res = $tbRetailService->guardarConteosTbRetail('tienda', $f, $tiendas);
                if ($tiendas === null) {
                    $tbRetailService->guardarConteosTbRetail('marca', $f);
                }
                $this->line("$f OK (" . count($res) . ' tiendas con dato)');
            } catch (\Exception $e) {
                $fallidos[] = $f;
                $this->error("$f ERROR: " . $e->getMessage());
            }
        }

        if ($tiendas !== null) {
            $this->recalcularMarcas($ini->toDateString(), $fin->toDateString());
        }

        if ($fallidos) {
            $this->warn('Días con error (reintentar): ' . implode(', ', $fallidos));
            return Command::FAILURE;
        }
        $this->info('Backfill terminado.');
        return Command::SUCCESS;
    }

    /** Recalcula tipo=marca del rango como suma de las tiendas (sin llamar a la API). */
    private function recalcularMarcas(string $ini, string $fin): void
    {
        $n = DB::connection('pgsql')->affectingStatement("
            INSERT INTO tbretail_conteos (fecha, tipo, entidad_id, conteo, created_at, updated_at)
            SELECT c.fecha, 'marca', m.idmarca, SUM(c.conteo), now(), now()
            FROM tbretail_conteos c
            JOIN (SELECT DISTINCT idtienda, idmarca FROM location_id_tienda) m ON m.idtienda = c.entidad_id
            WHERE c.tipo = 'tienda' AND c.fecha BETWEEN ? AND ?
            GROUP BY c.fecha, m.idmarca
            ON CONFLICT (fecha, tipo, entidad_id)
            DO UPDATE SET conteo = EXCLUDED.conteo, updated_at = now()
        ", [$ini, $fin]);
        $this->info("Totales por marca recalculados: $n filas.");
    }
}
