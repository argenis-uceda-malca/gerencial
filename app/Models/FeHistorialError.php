<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Copia consolidada de errores por documento. Complementa (no
 * reemplaza) SPE_ERROR_LOG / bl_mensajeSunat / bl_mensaje de cada
 * tienda, y también registra errores propios de la etapa de captura.
 */
class FeHistorialError extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_historial_errores';

    const CREATED_AT = 'fecha_error';
    const UPDATED_AT = null;

    protected $fillable = [
        'id_control_registro', 'codigo_error', 'mensaje_original', 'resuelto',
        'accion_correctiva', 'usuario_resolucion', 'fecha_resolucion',
    ];

    protected $casts = [
        'resuelto' => 'boolean',
        'fecha_resolucion' => 'datetime',
    ];
}
