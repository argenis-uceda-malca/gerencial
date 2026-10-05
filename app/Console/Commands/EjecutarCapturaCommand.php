<?php

namespace App\Console\Commands;

use App\Models\FeTienda;
use App\Services\Captura\MotorCapturaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Punto de entrada del scheduler. Solo orquesta el recorrido de
 * tiendas activas; toda la lógica de negocio vive en los servicios de
 * app/Services/Captura, para poder reutilizarla desde otro lugar (ej.
 * un botón "forzar captura ahora" en la web) sin duplicar código.
 */
class EjecutarCapturaCommand extends Command
{
    protected $signature = 'captura:ejecutar
                            {--tiendas= : Códigos de tienda separados por coma (ej. CENTRAL,AQP01)}
                            {--tienda= : Alias de --tiendas para compatibilidad}';

    protected $description = 'Detecta ventas nuevas en cada tienda activa y las inserta en su base intermedia de Bizlinks';

    /** @var MotorCapturaService */
    private $motor;

    public function __construct(MotorCapturaService $motor)
    {
        parent::__construct();
        $this->motor = $motor;
    }

    public function handle(): int
    {
        Cache::put('captura:motor:corriendo', now()->toIso8601String(), 600);

        try {
            $query = FeTienda::where('estado', 'ACTIVA');
            $filtro = $this->option('tiendas') ?: $this->option('tienda');
            if ($filtro) {
                $codigos = array_filter(array_map('trim', explode(',', $filtro)));
                $query->whereIn('codigo_tienda', $codigos);
            }
            $tiendas = $query->get();

            if ($tiendas->isEmpty()) {
                $this->info('No hay tiendas activas configuradas en FE_TIENDAS.');
                return self::SUCCESS;
            }

            foreach ($tiendas as $tienda) {
                $this->info("Procesando tienda {$tienda->codigo_tienda}...");
                $resumen = $this->motor->procesarTienda($tienda);
                $this->info("  Capturados: {$resumen['capturados']} | Cuarentena: {$resumen['cuarentena']} | Errores: {$resumen['errores']}");
            }

            return self::SUCCESS;
        } finally {
            Cache::forget('captura:motor:corriendo');
        }
    }
}
