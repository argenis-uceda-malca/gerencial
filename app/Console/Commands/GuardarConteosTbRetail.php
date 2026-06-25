<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TbRetailService;

class GuardarConteosTbRetail extends Command
{
    protected $signature = 'tbretail:guardar-conteos {fecha?}';

    protected $description = 'Guarda diariamente los conteos de TB Retail';

    public function handle(TbRetailService $tbRetailService)
    {
        $fecha = $this->argument('fecha');

        $tbRetailService->guardarConteosTbRetail('marca', $fecha);
        $tbRetailService->guardarConteosTbRetail('tienda', $fecha);

        $this->info('Conteos guardados correctamente.');

        return Command::SUCCESS;
    }
}