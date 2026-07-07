<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefrescarUltimosDiasEtl extends Command
{
    protected $signature = 'etl:refrescar-ultimos-dias {dias=3}';

    protected $description = 'Ejecuta ventas_diarias + reporte_ventas para los ultimos N dias';

    public function handle()
    {
        $dias = (int) $this->argument('dias');
        $fin  = now()->toDateString();
        $ini  = now()->subDays($dias)->toDateString();

        $db = DB::connection('pgsql');

        $this->info("Inicio ETL rapido: {$ini} → {$fin}");

        $db->select("SELECT automatizacion_sp_pla_ventas_diarias(?::date, ?::date)", [$ini, $fin]);
        $this->info('OK: automatizacion_sp_pla_ventas_diarias');

        $db->select("SELECT automatizacion_sp_reporte_ventas(?::date, ?::date, FALSE)", [$ini, $fin]);
        $this->info('OK: automatizacion_sp_reporte_ventas');

        $this->info('ETL rapido completado.');

        return Command::SUCCESS;
    }
}
