<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RefrescarStockSemanal extends Command
{
    protected $signature = 'etl:refrescar-stock
                            {--fecha= : Fecha de corte YYYY-MM-DD (default: ayer)}';

    protected $description = 'Bootstrap completo de stock act+hst y regenera los bloques en el reporte';

    public function handle()
    {
        $db = DB::connection('pgsql');

        $fechaAct = $this->option('fecha')
            ? Carbon::parse($this->option('fecha'))->toDateString()
            : now()->subDay()->toDateString();

        $fechaHst = Carbon::parse($fechaAct)->subYear()->toDateString();

        Log::info('etl:refrescar-stock INICIO', ['act' => $fechaAct, 'hst' => $fechaHst]);
        $this->info("Refrescando stock — act: {$fechaAct}  hst: {$fechaHst}");

        $this->info('1/4  Bootstrap stock actual...');
        $db->select('SELECT automatizacion_sp_insertar_stock_semana(?::date, NULL)', [$fechaAct]);
        $this->info('     OK');

        $this->info('2/4  Bootstrap stock historico...');
        $yaExiste = $db->selectOne(
            'SELECT 1 FROM automatizacion_stock_semanal WHERE fecha = ?::date LIMIT 1',
            [$fechaHst]
        );
        if ($yaExiste) {
            $this->info("     Omitido — {$fechaHst} ya calculado");
        } else {
            $db->select('SELECT automatizacion_sp_insertar_stock_semana(?::date, NULL)', [$fechaHst]);
            $this->info('     OK');
        }

        $this->info('3/4  Limpiando bloques de stock en reporte...');
        $db->statement("DELETE FROM automatizacion_pla_reporte_ventas WHERE tipo_fila IN ('stock_act','stock_hst')");
        $this->info('     OK');

        $this->info('4/4  Regenerando bloques de stock en reporte...');
        $db->select('SELECT automatizacion_sp_reporte_ventas(?::date, ?::date, FALSE)', [$fechaAct, $fechaAct]);
        $this->info('     OK');

        Log::info('etl:refrescar-stock FIN OK', ['act' => $fechaAct, 'hst' => $fechaHst]);
        $this->info('Stock actualizado.');

        return Command::SUCCESS;
    }
}
