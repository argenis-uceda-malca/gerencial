<?php

namespace App\Console\Commands;

use App\Mail\AlertaVentasMail;
use App\Services\MonitoreoVentasService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class MonitorearVentasCommand extends Command
{
    protected $signature = 'ventas:monitorear
                            {--force : Enviar alerta aunque no haya anomalías (prueba)}
                            {--solo-log : Solo loguear, no enviar email}';

    protected $description = 'Detecta anomalías en ventas y ETL, envía alertas por email';

    public function __construct(private MonitoreoVentasService $monitor)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $data    = $this->monitor->analizar();
        $alertas = $data['alertas'];
        $ahora   = $data['ahora'];

        $this->info("[{$ahora->toDateTimeString()}] ETL: {$data['etl']['horas']}h | "
                  . "Ventas hoy: S/ " . number_format($data['ventas']['total'], 2, '.', ','));

        if (empty($alertas) && !$this->option('force')) {
            $this->info('Sin anomalías detectadas.');
            return self::SUCCESS;
        }

        if (empty($alertas) && $this->option('force')) {
            $alertas[] = [
                'tipo'    => 'prueba',
                'nivel'   => 'info',
                'titulo'  => 'Prueba de alertas',
                'detalle' => 'Esta es una alerta de prueba generada con --force.',
            ];
        }

        foreach ($alertas as $a) {
            $nivel = strtoupper($a['nivel']);
            $this->warn("  [{$nivel}] {$a['titulo']}");
            $this->line("         {$a['detalle']}");
            Log::channel('daily')->warning("[MONITOR-VENTAS] {$a['titulo']}", $a);
        }

        if ($this->option('solo-log')) {
            $this->info('Modo solo-log: email omitido.');
            return self::SUCCESS;
        }

        $destinatarios = array_filter(explode(',', config('mail.monitor_destinatarios', '')));

        if (empty($destinatarios)) {
            $this->warn('MAIL_MONITOR_DESTINATARIOS no configurado — alerta guardada solo en log.');
            return self::SUCCESS;
        }

        try {
            Mail::to($destinatarios)->send(new AlertaVentasMail($alertas, $ahora));
            $this->info('Email enviado a: ' . implode(', ', $destinatarios));
        } catch (\Exception $e) {
            $this->error('Error al enviar email: ' . $e->getMessage());
            Log::error('[MONITOR-VENTAS] Fallo al enviar email', ['error' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }
}
