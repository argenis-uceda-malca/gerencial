<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefrescarFiltroSss extends Command
{
    protected $signature = 'etl:refrescar-filtro-sss';

    protected $description = 'Ejecuta filtro_sss (clasificacion SSS/NUEVO/CIERRE)';

    public function handle()
    {
        $db = DB::connection('pgsql');

        $this->info('Iniciando automatizacion_sp_filtro_sss...');

        $db->statement("SELECT automatizacion_sp_filtro_sss(NULL, NULL)");

        $this->info('OK: filtro_sss actualizado.');

        return Command::SUCCESS;
    }
}
