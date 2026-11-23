<?php

namespace App\Console\Commands;

use App\Services\ReporteEjecutivoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ReporteEjecutivoCommand extends Command
{
    protected $signature = 'reporte:ejecutivo
                            {--ini= : Fecha inicio (Y-m-d). Por defecto 1-ene del año}
                            {--fin= : Fecha fin (Y-m-d). Por defecto ayer}
                            {--para= : Destinatarios separados por coma (si no, MAIL_REPORTE_EJECUTIVO_DESTINATARIOS)}
                            {--solo-generar : Genera Excel e imágenes en storage/app/reporte_ejecutivo sin enviar correo}';

    protected $description = 'Genera y envía por correo el reporte ejecutivo de ventas (Excel + imágenes)';

    /** @var ReporteEjecutivoService */
    private $svc;

    public function __construct(ReporteEjecutivoService $svc)
    {
        parent::__construct();
        $this->svc = $svc;
    }

    public function handle()
    {
        list($ini, $fin) = $this->svc->rangoPorDefecto();
        if ($this->option('ini')) { $ini = $this->option('ini'); }
        if ($this->option('fin')) { $fin = $this->option('fin'); }

        $solo = (bool)$this->option('solo-generar');
        $csv  = $this->option('para') ?: config('mail.reporte_ejecutivo_destinatarios', '');
        $dest = array_values(array_filter(array_map('trim', explode(',', (string)$csv))));

        if (!$solo && empty($dest)) {
            $this->error('Sin destinatarios: define MAIL_REPORTE_EJECUTIVO_DESTINATARIOS en .env o usa --para=.');
            return 1;
        }

        $t0 = microtime(true);
        try {
            $r = $this->svc->ejecutar($ini, $fin, $dest, $solo);
        } catch (\Exception $e) {
            Log::error('[REPORTE-EJECUTIVO] Falló', ['error' => $e->getMessage()]);
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        $seg = round(microtime(true) - $t0, 1);
        if ($solo) {
            $this->info("Generado en {$r['dir']} ({$seg}s)");
        } else {
            $this->info('Enviado a: ' . implode(', ', $dest) . " ({$seg}s)");
            Log::info('[REPORTE-EJECUTIVO] Enviado', ['para' => $dest, 'ini' => $ini, 'fin' => $fin, 'seg' => $seg]);
        }
        return 0;
    }
}
