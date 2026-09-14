<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Registro de cada tienda: cómo conectarse a su servidor (SOLUFLEX_FARO
 * y la base intermedia de Bizlinks) y el cursor de captura.
 */
class FeTienda extends Model
{
    protected $connection = 'central'; // Conexión PostgreSQL de control (ver config/database.php)
    protected $table = 'fe_tiendas';
    protected $primaryKey = 'codigo_tienda';
    public $incrementing = false;
    protected $keyType = 'string';

    const CREATED_AT = 'fecha_registro';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'codigo_tienda', 'nombre_tienda', 'idempresa_soluflex', 'idsucursal_soluflex',
        'servidor_host', 'servidor_puerto', 'bd_soluflex_nombre', 'bd_bizlinks_nombre',
        'usuario_conexion', 'password_conexion_cifrado', 'estado',
        'ultimo_idtransaccion_capturado', 'fecha_ultima_captura', 'fecha_ultimo_monitoreo',
        'numero_serie_nc',
    ];

    protected $casts = [
        'fecha_ultima_captura' => 'datetime',
        'fecha_ultimo_monitoreo' => 'datetime',
        'servidor_puerto' => 'integer',
        'ultimo_idtransaccion_capturado' => 'integer',
        'numero_serie_nc' => 'integer',
    ];

    public function controlRegistros()
    {
        return $this->hasMany(FeControlRegistro::class, 'codigo_tienda', 'codigo_tienda');
    }
}
