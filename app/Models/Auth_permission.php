<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PhpParser\Node\Stmt\Return_;

use App\Models\User;

class Auth_permission extends Model
{
    use HasFactory;
    protected $table = "auth_permission";
    protected $connection = "pgsql2";

    public function get_permisos_id($permisosid)
    {

        $resultado = Auth_permission::whereIn('id', $permisosid)->get();
        $permisosid = $resultado->pluck('codename')->toArray();
        return $permisosid;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'auth_user_user_permissions', 'permission_id', 'user_id');
    }
}
