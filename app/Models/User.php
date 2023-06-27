<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use App\Models\Auth_user_permissions;
use App\Models\Auth_permission;
use Illuminate\Support\Facades\DB;

use Spatie\Permission\Traits\HasPermissions;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use HasPermissions;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $connection = "pgsql2";
    protected $table = 'auth_user';

    protected $fillable = [
        'id',
        'password',
        'email',
        'last_login',
        'is_superuser',
        'username',
        'first_name ',
        'last_name',
        'email',
        'is_staff',
        'is_active',
        'date_joined',
        'migrated_password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function Auth_permission()
    {
        return $this->belongsToMany(Auth_permission::class, 'auth_user_user_permissions', 'user_id', 'permission_id');
    }

    public function tienePermiso($permiso, $userid)
    {
        $bi_conexion = DB::connection('pgsql2');
        $resultado = $bi_conexion->table('auth_user_user_permissions')
        //$resultado = $bi_conexion->select("SELECT permission_id FROM auth_user_user_permissions WHERE username = $username ");
            ->where('user_id', $userid)
            ->pluck('permission_id')
            ->toArray();

            //var_dump($resultado);

        if (!empty($resultado)) {
            $coleccion = collect($resultado);
            return $coleccion->contains($permiso);
        }

        return false;
    }
}
