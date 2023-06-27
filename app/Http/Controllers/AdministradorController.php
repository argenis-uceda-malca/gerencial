<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\Auth_user_permissions;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class AdministradorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        // $lista_usuario = new user;
        // $lista_usuario = $lista_usuario->all();

        // $lista_usuario = Cache::remember('users',60, function () {
        //     return User::whereNotNull('first_name')
        //         ->select('id', 'username', 'first_name','last_name', 'email')
        //         ->simplePaginate(10);
        // });
        
        $lista_usuario = User::whereNotNull('first_name')
        ->select('id', 'username', 'first_name','last_name', 'email')
        ->simplePaginate(10); // 10 usuarios por página

        return view('lista_usuarios', compact('lista_usuario'));
    }


    public function cambiar(Request $request)
    {
        $user_permissions = new Auth_user_permissions;
        $permisoid = $request->input('permisoid');
        $idusuario = $request->input('usuarioid');

        // Actualiza la columna en la base de datos según el permisoId y isChecked
        // Por ejemplo, utilizando el modelo Permiso:
        //$usuario->where('id', $permisoId)->update(['columna' => $isChecked]);

        //validar si cuenta con el permiso
        $busqueda = $user_permissions->where('user_id', $idusuario)->where('permission_id', $permisoid);

        //return response()->json(['message' => $permisoid .' , '.   $idusuario]);
        if ($busqueda == NULL) {
            /**Se  */
            //aqui le falta un where para el usuario_id: $user_permissions->where('permission_id', $permisoid)->delete();
            return response()->json(['message' => $permisoid . $idusuario]);
        } else {
            // $user_permissions::insert([
            //     'user_id' => $idusuario,
            //     'permission_id' => $permisoid
            // ]);
            return response()->json(['message' => 'insertado']);
        }
        return response()->json(['message' => $idusuario]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Administrador  $administrador
     * @return \Illuminate\Http\Response
     */
    public function show(Administrador $administrador)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Administrador  $administrador
     * @return \Illuminate\Http\Response
     */
    public function edit(Administrador $administrador)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Administrador  $administrador
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Administrador $administrador)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Administrador  $administrador
     * @return \Illuminate\Http\Response
     */
    public function destroy(Administrador $administrador)
    {
        //
    }
}
