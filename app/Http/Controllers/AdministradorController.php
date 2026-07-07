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
            ->select('id', 'username', 'first_name', 'last_name', 'email')
            ->simplePaginate(50); // 10 usuarios por página
        // $lista_usuario = User::whereNotNull('first_name')
        //     ->select('id', 'username', 'first_name', 'last_name', 'email')
        //     ->paginate(30); // 30 usuarios por página

        //var_dump($lista_usuario);
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
        $busqueda = $user_permissions->where('user_id', $idusuario)->where('permission_id', $permisoid)->get();

        //return response()->json(['message' => $busqueda]);
        if ($busqueda->isEmpty()) {
            /**Si el usuario no tiene permisos le insertamos  */
            //$user_permissions->insert();
            $usuario_insert = array('user_id' => $idusuario, "permission_id" => $permisoid);
            $user_permissions->insert($usuario_insert);

            return response()->json(['message' => 'insertado']);
        } else {
            //Si el usuario le encontramos permisos se lo eliminamos 
            //aqui le falta un where para el usuario_id: 
            $user_permissions->where('permission_id', $permisoid)->where('user_id', $idusuario)->delete();
            return response()->json(['message' => 'eliminado']);
        }
        //return response()->json(['message' => $idusuario]);
    }

    public function get_usuario(Request $request)
    {
        $nombre = $request->input('nombre');
        $apellido = $request->input('apellido');
        $username = $request->input('username');
        try {
            $lista_usuario = User::where(function ($query) use ($nombre, $apellido, $username) {
                if (!empty($nombre)) {
                    $query->orWhereRaw('LOWER(first_name) LIKE ?', ["%" . strtolower($nombre) . "%"]);
                }

                if (!empty($apellido)) {
                    $query->orWhereRaw('LOWER(last_name) LIKE ?', ["%" . strtolower($apellido) . "%"]);
                }

                if (!empty($username)) {
                    $query->orWhereRaw('LOWER(username) LIKE ?', ["%" . strtolower($username) . "%"]);
                }
            })
                ->select('id', 'username', 'first_name', 'last_name', 'email')
                ->simplePaginate(10);

            return view('lista_usuarios', compact('lista_usuario'));
        } catch (\Exception $e) {
            dd($e->getMessage()); // Imprime el mensaje de error en la pantalla
        }
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
