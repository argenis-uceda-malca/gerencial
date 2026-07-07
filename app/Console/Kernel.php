<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('tbretail:guardar-conteos')
             ->dailyAt('02:00');

        // Actualiza ventas cada 10 min (ventas_diarias + reporte_ventas, ultimos 3 dias)
        $schedule->command('etl:refrescar-ultimos-dias')
             ->everyTenMinutes()
             ->withoutOverlapping(5);

        // Clasificacion SSS/NUEVO/CIERRE: una vez al dia (suficiente)
        $schedule->command('etl:refrescar-filtro-sss')
             ->dailyAt('04:00')
             ->withoutOverlapping(10);
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {

        \App\Console\Commands\GuardarConteosTbRetail::class;
        \App\Console\Commands\RefrescarUltimosDiasEtl::class;
        \App\Console\Commands\RefrescarFiltroSss::class;

        $this->load(__DIR__.'/Commands');
        

        require base_path('routes/console.php');
    }
}
