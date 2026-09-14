<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Tabla central: una fila por cada venta de Soluflex que el módulo
 * detecta, con su estado a lo largo de todo el ciclo de vida.
 *
 * La restricción UNIQUE (codigo_tienda, idtransaccion_soluflex) en la
 * base de datos es la que garantiza idempotencia: ver
 * MotorCapturaService::procesarVenta().
 */
class FeControlRegistro extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_control_registros';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_ultima_actualizacion';

    protected $fillable = [
        'codigo_tienda', 'idtransaccion_soluflex', 'tipo_documento_sunat',
        'serie_numero_bizlinks', 'numero_documento_emisor', 'numero_documento_cliente',
        'razon_social_cliente', 'importe_total', 'fecha_venta', 'estado',
        'motivo_cuarentena', 'intentos_captura', 'fecha_captura',
    ];

    protected $casts = [
        'fecha_venta' => 'datetime',
        'fecha_captura' => 'datetime',
        'importe_total' => 'decimal:2',
        'idtransaccion_soluflex' => 'integer',
        'intentos_captura' => 'integer',
    ];

    public function tienda()
    {
        return $this->belongsTo(FeTienda::class, 'codigo_tienda', 'codigo_tienda');
    }

    public function auditoria()
    {
        return $this->hasMany(FeAuditoriaEstado::class, 'id_control_registro');
    }

    public function errores()
    {
        return $this->hasMany(FeHistorialError::class, 'id_control_registro');
    }

    public function reintentos()
    {
        return $this->hasMany(FeHistorialReintento::class, 'id_control_registro');
    }

    public function notificaciones()
    {
        return $this->hasMany(FeNotificacion::class, 'id_control_registro');
    }
}
