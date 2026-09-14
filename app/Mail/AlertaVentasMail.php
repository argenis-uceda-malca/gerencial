<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaVentasMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array  $alertas,
        public Carbon $ahora
    ) {}

    public function build(): static
    {
        $criticos = count(array_filter($this->alertas, fn($a) => $a['nivel'] === 'critico'));
        $warnings = count(array_filter($this->alertas, fn($a) => $a['nivel'] !== 'critico'));

        $partes = [];
        if ($criticos) $partes[] = "{$criticos} CRÍTICO(S)";
        if ($warnings)  $partes[] = "{$warnings} aviso(s)";
        $resumen = implode(' + ', $partes);

        return $this->subject("⚠️ Alerta ventas Smart Brands — {$resumen} — {$this->ahora->format('d/m/Y H:i')}")
                    ->view('emails.alertas_ventas');
    }
}
