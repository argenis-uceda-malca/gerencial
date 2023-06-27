<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Auth_user_permissions extends Model
{
    use HasFactory;
    protected $table = "auth_user_user_permissions";
    protected $connection = "pgsql2";

    public function validadar_usuario_permiso($iduser)
    {
        $auth_Permission = new Auth_permission();
        $permisos_user = $this->where('user_id', $iduser)->get();
        $permisosid = $permisos_user->pluck('permission_id')->toArray();

        $respuesta = $auth_Permission->get_permisos_id($permisosid);

        return $respuesta;
    }


}
