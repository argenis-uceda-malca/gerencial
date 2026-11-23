<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Mail\Mailable;

/**
 * Reporte ejecutivo de ventas: cuerpo con resumen + imágenes incrustadas + Excel adjunto.
 * Se envía de inmediato (no se encola) porque los archivos son temporales.
 */
class ReporteEjecutivoMail extends Mailable
{
    /** @var array */
    public $an;
    /** @var array */
    public $imgs;
    /** @var string */
    public $excel;

    public function __construct(array $an, array $imgs, $excel)
    {
        $this->an    = $an;
        $this->imgs  = $imgs;
        $this->excel = $excel;
    }

    public function build()
    {
        $fin = Carbon::parse($this->an['fin']);

        return $this->subject('Reporte de ventas ' . $fin->format('Y') . ' al ' . $fin->format('d/m/Y') . ' - Smart Brands')
            ->view('emails.reporte_ejecutivo')
            ->attach($this->excel, [
                'as'   => basename($this->excel),
                'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);
    }
}
