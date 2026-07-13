<?php

namespace App\Http\Controllers;

use App\Models\Administrador;
use App\Models\Auth_user_permissions;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

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

    public function syncUsers()
    {
        try {
            Log::info('[SyncUsers] Iniciando sincronización');

            $client = new Client(['base_uri' => 'https://apirest.sbperu.com', 'verify' => false]);
            $response = $client->request('POST', '/oauth/token', [
                'headers' => ["Accept" => "application/json"],
                'form_params' => [
                    "grant_type" => "client_credentials",
                    "client_id" => 7,
                    "client_secret" => "bZtI2r4liDPMoSo1MLow3LrDEVjDYBBzUCSpNVOt",
                    "scope" => ""
                ]
            ]);
            $token = json_decode($response->getBody()->getContents());
            Log::info('[SyncUsers] Token OAuth obtenido');

            $response = $client->request('POST', '/v2/smartapp/net', [
                'headers' => [
                    "Authorization" => "{$token->token_type} {$token->access_token}",
                    "Accept" => "application/json"
                ],
                'form_params' => [
                    "type" => "listarusuariossoluflex",
                    "env" => config('app.env')
                ]
            ]);

            $rawBody = $response->getBody()->getContents();
            Log::info('[SyncUsers] Respuesta API (primeros 500 chars): ' . substr($rawBody, 0, 500));

            $apiUsers = json_decode($rawBody, true);

            if (empty($apiUsers)) {
                Log::warning('[SyncUsers] API devolvió vacío o null');
                return response()->json([
                    'success' => false,
                    'message' => 'No se obtuvieron usuarios de la API'
                ]);
            }

            Log::info('[SyncUsers] Total usuarios desde API: ' . count($apiUsers));

            // Log primer usuario como muestra para ver los campos
            if (count($apiUsers) > 0) {
                Log::info('[SyncUsers] Muestra primer usuario: ' . json_encode($apiUsers[0]));
            }

            $creados = 0;
            $actualizados = 0;
            $errores = 0;

            foreach ($apiUsers as $i => $apiUser) {
                $username = trim($apiUser['CODIGO_USUARIO'] ?? '');
                if (empty($username)) {
                    Log::warning("[SyncUsers] Usuario #{$i} sin CODIGO_USUARIO, se salta");
                    continue;
                }

                $nombre = mb_substr(trim($apiUser['NOMBRE'] ?? ''), 0, 30);
                $email = trim($apiUser['EMAIL'] ?? '');
                $isActive = ($apiUser['CODIGO_ESTADO'] ?? '') === '01';

                try {
                    $existingUser = User::where('username', $username)->first();

                    if ($existingUser) {
                        $existingUser->update([
                            'first_name' => $nombre ?: $existingUser->first_name,
                            'email' => $email ?: $existingUser->email,
                            'is_active' => $isActive,
                        ]);
                        $actualizados++;
                        Log::info("[SyncUsers] Actualizado: {$username}");
                    } else {
                        User::create([
                            'username' => $username,
                            'password' => '',
                            'last_name' => '',
                            'first_name' => $nombre,
                            'email' => $email,
                            'is_active' => $isActive,
                            'is_staff' => false,
                            'is_superuser' => false,
                            'date_joined' => now(),
                        ]);
                        $creados++;
                        Log::info("[SyncUsers] CREADO nuevo: {$username} - {$nombre}");
                    }
                } catch (\Exception $e) {
                    $errores++;
                    Log::error("[SyncUsers] Error con {$username}: " . $e->getMessage());
                }
            }

            Log::info("[SyncUsers] Finalizado: {$creados} creados, {$actualizados} actualizados, {$errores} errores");

            return response()->json([
                'success' => true,
                'creados' => $creados,
                'actualizados' => $actualizados,
                'errores' => $errores,
                'total_api' => count($apiUsers),
                'message' => "{$creados} creados, {$actualizados} actualizados, {$errores} errores"
            ]);

        } catch (\Exception $e) {
            Log::error('[SyncUsers] Excepción general: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al sincronizar: ' . $e->getMessage()
            ], 500);
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
