<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeNotificacion extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_notificaciones';

    const CREATED_AT = 'fecha_generacion';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_control_registro', 'tipo', 'destinatario', 'asunto',
        'estado_envio', 'error_envio', 'fecha_envio',
    ];

    protected $casts = ['fecha_envio' => 'datetime'];
}
