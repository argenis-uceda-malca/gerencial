<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaVentasMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @var array */
    public $alertas;
    /** @var Carbon */
    public $ahora;

    public function __construct(array $alertas, Carbon $ahora)
    {
        $this->alertas = $alertas;
        $this->ahora   = $ahora;
    }

    public function build()
    {
        $criticos = count(array_filter($this->alertas, function ($a) { return $a['nivel'] === 'critico'; }));
        $warnings = count(array_filter($this->alertas, function ($a) { return $a['nivel'] !== 'critico'; }));

        $partes = [];
        if ($criticos) $partes[] = "{$criticos} CRÍTICO(S)";
        if ($warnings)  $partes[] = "{$warnings} aviso(s)";
        $resumen = implode(' + ', $partes);

        return $this->subject("Alerta ventas Smart Brands - {$resumen} - {$this->ahora->format('d/m/Y H:i')}")
                    ->view('emails.alertas_ventas');
    }
}
