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

use App\Models\User;

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
            return redirect('/reporte');
        } else {
            echo "no logeueado";
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
}
