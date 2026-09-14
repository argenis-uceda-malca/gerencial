<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeAuditoriaEstado extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_auditoria_estados';

    const CREATED_AT = 'fecha_evento';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_control_registro', 'estado_anterior', 'estado_nuevo', 'origen',
        'detalle', 'usuario', 'ip_origen',
    ];
}
