<?php

namespace App\Console\Commands;

use App\Models\FeTienda;
use App\Services\Captura\MotorCapturaService;
use Illuminate\Console\Command;

/**
 * Punto de entrada del scheduler. Solo orquesta el recorrido de
 * tiendas activas; toda la lógica de negocio vive en los servicios de
 * app/Services/Captura, para poder reutilizarla desde otro lugar (ej.
 * un botón "forzar captura ahora" en la web) sin duplicar código.
 */
class EjecutarCapturaCommand extends Command
{
    protected $signature = 'captura:ejecutar';

    protected $description = 'Detecta ventas nuevas en cada tienda activa y las inserta en su base intermedia de Bizlinks';

    public function __construct(private MotorCapturaService $motor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $tiendas = FeTienda::where('estado', 'ACTIVA')->get();

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
    }
}
