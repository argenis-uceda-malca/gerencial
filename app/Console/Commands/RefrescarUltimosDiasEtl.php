<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        Log::info("etl:refrescar-ultimos-dias INICIO", ['ini' => $ini, 'fin' => $fin]);
        $this->info("Inicio ETL rapido: {$ini} → {$fin}");

        $db->select("SELECT automatizacion_sp_pla_ventas_diarias(?::date, ?::date)", [$ini, $fin]);
        Log::info("etl:refrescar-ultimos-dias OK sp_pla_ventas_diarias");
        $this->info('OK: automatizacion_sp_pla_ventas_diarias');

        $db->select("SELECT automatizacion_sp_reporte_ventas(?::date, ?::date, FALSE)", [$ini, $fin]);
        Log::info("etl:refrescar-ultimos-dias OK sp_reporte_ventas");
        $this->info('OK: automatizacion_sp_reporte_ventas');

        Log::info("etl:refrescar-ultimos-dias FIN OK");
        $this->info('ETL rapido completado.');

        return Command::SUCCESS;
    }
}
