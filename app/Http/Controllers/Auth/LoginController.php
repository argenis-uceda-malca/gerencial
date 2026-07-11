<?php

namespace App\Http\Controllers\Auth;

use App\Models\Auth_permission;
use App\Models\Auth_user_permissions;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
//use GuzzleHttp\Psr7\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Session;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

use App\Models\User;
//use Illuminate\Contracts\Session\Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function index()
    {
        return view('auth/login');
    }

    // public function login(Request $request)
    // {
    //     // Comprobamos que el email y la contraseña han sido introducidos
    //     $request->validate([
    //         'username' => 'required',
    //         'password' => 'required',
    //     ]);

    //     // Almacenamos las credenciales de usuario y contraseña
    //     $credentials = $request->only('username', 'password');

    //     // Si el usuario existe lo logamos y lo llevamos a la vista de "logados" con un mensaje
    //     if (Auth::attempt($credentials)) {
    //         //return redirect()->intended('logados')->withSuccess('Logado Correctamente');
    //         echo "exito";
    //     }

    //     /** */
    // }

    public function username()
    {
        return 'username';
    }

    public function login(Request $request)
    {
        // Comprobamos que el email y la contraseña han sido introducidos
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);

        // Almacenamos las credenciales de usuario y contraseña
        $credentials = $request->only('username', 'password');

        // Ajustamos el valor de 'username' entre comillas para tratarlo como un varchar en la consulta
        $username = "'" . $credentials['username'] . "'";
        $password = $credentials['password'];

        // Ejecutamos la consulta manualmente
        $bi_conexion = DB::connection('pgsql2');
        $user = $bi_conexion->select("SELECT * FROM auth_user WHERE username = $username LIMIT 1");

        // Verificamos si se encontró un usuario y si el password coincide
        if (!empty($user) && Hash::check($password, $user[0]->migrated_password)) {

            session(
                [
                    'id' => $user[0]->id,
                    'username' => $user[0]->username,
                    'first_name' => $user[0]->first_name,
                    'last_name' => $user[0]->last_name,
                    'email' => $user[0]->email,
                    'last_name' => $user[0]->last_name,
                ]
            );
            $this->activarpermisos($user[0]->id);
            //var_dump(session('username'));
            return redirect('/inicio');
        } else {
            return redirect('/');
        }
    }

    function check_password($password, $hashedPassword)
    {
        $patron = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz';
        $crip = '^çºªæÆöûÿø£Ø×ƒ¬½¼¡«»ÄÅÉêèï7485912360!><#~$€%&/()=?¿[]:;}{@|\*¶';

        $dict = array_combine(str_split($patron), str_split($crip));
        $passw_hash = "";

        foreach (str_split(strtoupper($password)) as $index) {
            if (isset($dict[$index])) {
                $passw_hash .= $dict[$index];
            } else {
                $passw_hash .= $index;
            }
        }

        return Hash::check($passw_hash, $hashedPassword);
    }

    //crear un metodo que inserte en la columna de "migrated_password" un hash creado a partir de la columna last_name y firt_name que se encuentran en mi tabla de auth_user, la columna "migrated_password" tambien se encuentra en la misma tabla 

    //Función para insertar una clave hash en la nueva columna "migrated_password" 
    public function migratePasswords()
    {
        $bi_conexion = DB::connection('pgsql2');
        $users = $bi_conexion->table('auth_user')->select('id', 'username')->whereNull('migrated_password')->get();

        foreach ($users as $user) {
            $migratepass = $user->username;
            $migratedPassword = Hash::make($migratepass);

            //echo $migratedPassword;

            $bi_conexion->table('auth_user')->where('id', $user->id)->update(['migrated_password' => $migratedPassword]);
        }

        return 'Contraseñas migradas exitosamente.';
    }

    public function activarpermisos($id)
    {

        $auth_user_Permission = new Auth_user_permissions();
        $resultado = $auth_user_Permission->validadar_usuario_permiso($id);

        if (isset($resultado)) {
            session()->put('permisos', $resultado);
        }

        //var_dump(session('permisos'));

    }

    public function cerrar()
    {
        //Session::flush();
        //session()->forget(['id', 'username', 'first_name', 'last_name', 'email']);
        Session::flush();
        return redirect('/');
    }

    // public function prueba_login0(Request $request) {
    //     $token = rest_api_token();

    //     $client = new Client(['base_uri' => get_url_api_rest(),'verify' => false ]);
    //     var_dump($client);
    // }

    public function getUserSB(Request $request)
    {

        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        $credentials = $request->only('username', 'password');
        $user = $credentials['username'];

        //dd($user);

        // --------------------------------------------------
        try {
            $client = new Client(['base_uri' => 'https://apirest.sbperu.com', 'verify' => false]);
            $response = $client->request('POST', '/oauth/token', [
                'headers' => [
                    "Accept" => "application/json",
                    "Cache-control" => "no-cache"
                ],
                'form_params' => [
                    "grant_type" => "client_credentials",
                    "client_id" => 7,
                    "client_secret" => "bZtI2r4liDPMoSo1MLow3LrDEVjDYBBzUCSpNVOt",
                    "scope" => ""
                ]
            ]);

            

            $token = json_decode($response->getBody()->getContents());
            $client = new Client(['base_uri' => get_url_api_rest(),'verify' => false ]);
            $response = $client->request('POST', get_url_api_rest(), [
                'headers' => [
                    "Authorization" => "{$token->token_type} {$token->access_token}",
                    "Accept" => "application/json",
                    "Cache-control" => "no-cache"
                ],
                'form_params' => [
                    "type" => "login_user",
                    "identify" => $user,
                    "env" => config('app.env')
                ]
            ]);
            $user = json_decode($response->getBody()->getContents(), true);
            //dd($user);
            return $user;
        } catch (RequestException | ClientException $e) {
            return null;
        }
        
    }


    public function login2(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
        $credentials = $request->only('username', 'password');
        $password = $credentials['password'];

        // 1. Intentar autenticación vía API externa
        $apiUser = $this->getUserSB($request);

        if (!empty($apiUser) && isset($apiUser['CLAVE'])) {
            $password_encrypt = $this->des_encrypt_sb($apiUser['CLAVE']);

            if ($password_encrypt === $password) {
                session([
                    'username' => $apiUser['CODIGO_USUARIO'],
                    'first_name' => $apiUser['NOMBRE'],
                    'email' => $apiUser['EMAIL'],
                ]);
                // Cachear password local para fallback futuro
                $this->syncMigratedPassword($apiUser['CODIGO_USUARIO'], $password);
                $this->activarpermisos($this->getIdUser($apiUser['CODIGO_USUARIO']));
                return redirect('/inicio');
            }
        }

        // 2. Fallback local (API no disponible o usuario no encontrado)
        $bi_conexion = DB::connection('pgsql2');
        $localUser = $bi_conexion->table('auth_user')
            ->where('username', $credentials['username'])
            ->first();

        if ($localUser && !empty($localUser->migrated_password) && Hash::check($password, $localUser->migrated_password)) {
            session([
                'id' => $localUser->id,
                'username' => $localUser->username,
                'first_name' => $localUser->first_name,
                'last_name' => $localUser->last_name,
                'email' => $localUser->email,
            ]);
            $this->activarpermisos($localUser->id);
            return redirect('/inicio');
        }

        return redirect('/');
    }

    private function getIdUser($codigo){

        $codigo = "'" . $codigo . "'";
        //dd($codigo);
        $bi_conexion = DB::connection('pgsql2');
        $user = $bi_conexion->select("SELECT * FROM auth_user WHERE username = $codigo LIMIT 1");
        return $user[0]->id;
    }

    private function syncMigratedPassword($username, $password)
    {
        try {
            DB::connection('pgsql2')->table('auth_user')
                ->where('username', $username)
                ->update(['migrated_password' => Hash::make($password)]);
        } catch (\Exception $e) {
            // No bloquear el login si falla la sincronización
        }
    }

    private function normalize($user)
    {
        return [
            'name' => $user['NOMBRE'],
            $this->username() => strtolower(trim($user['CODIGO_USUARIO'])),
            'email' => trim($user['EMAIL']),
            'password' => bcrypt($this->des_encrypt_sb($user['CLAVE'])),
            'idsucursal' => $user['IDSUCURSAL'],
            'istienda' => $user['TIENDA'],
            'activated' => ($user['CODIGO_ESTADO'] == "01") ? 1 : 0
        ];
    }

    private function str_split_unicode($str, $l = 1)
    {
        if ($l > 0) {
            $ret = array();
            $len = mb_strlen($str, "UTF-8");
            for ($i = 0; $i < $len; $i += $l) {
                $ret[] = mb_substr($str, $i, $l, "UTF-8");
            }
            return $ret;
        }
        return preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
    }

    private function des_encrypt_sb($password)
    {
        $pass_desencrypt = "";
        $pass_sb = null;
        $crypt = $this->str_split_unicode('^çºªæÆöûÿø£Ø×ƒ¬½¼¡«»ÄÅÉêèï7485912360!><#~$€%&/()=?¿[]:;}{@|\*¶');
        $pass = $this->str_split_unicode('ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcdefghijklmnopqrstuvwxyz');
        $pass_sb = $this->str_split_unicode($password);
        foreach ($pass_sb as $k => $v) {
            foreach ($crypt as $key => $value) {
                if ($v == $value) {
                    // $pass_desencrypt .= $pass[$key];
                    $pass_desencrypt .= ($v == '@') ? '@' : $pass[$key];
                    break;
                }
            }
        }
        return $pass_desencrypt;
    }

}
