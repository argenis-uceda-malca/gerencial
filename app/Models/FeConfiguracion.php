<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeConfiguracion extends Model
{
    protected $connection = 'central';
    protected $table = 'fe_configuracion';
    protected $primaryKey = 'clave';
    public $incrementing = false;
    protected $keyType = 'string';

    const UPDATED_AT = 'fecha_actualizacion';
    const CREATED_AT = null;

    protected $fillable = ['clave', 'valor', 'descripcion'];

    public static function obtener(string $clave, $default = null)
    {
        return static::query()->where('clave', $clave)->value('valor') ?? $default;
    }
}
