<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeHistorialReintento extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_historial_reintentos';

    const CREATED_AT = 'fecha_reintento';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_control_registro', 'tipo_reintento', 'origen', 'usuario', 'resultado', 'mensaje',
    ];
}
