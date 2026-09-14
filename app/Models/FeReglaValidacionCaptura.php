<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeReglaValidacionCaptura extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_reglas_validacion_captura';

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = null;

    protected $fillable = ['codigo_error', 'descripcion', 'accion', 'valor_default', 'activo'];

    protected $casts = ['activo' => 'boolean'];
}
