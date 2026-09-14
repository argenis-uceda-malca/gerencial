<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeLogSistema extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_logs_sistema';

    const CREATED_AT = 'fecha';
    const UPDATED_AT = null;

    protected $fillable = ['nivel', 'componente', 'codigo_tienda', 'mensaje'];

    public static function log(string $nivel, string $componente, string $mensaje, ?string $codigoTienda = null): void
    {
        static::create([
            'nivel' => $nivel,
            'componente' => $componente,
            'codigo_tienda' => $codigoTienda,
            'mensaje' => $mensaje,
        ]);
    }
}
