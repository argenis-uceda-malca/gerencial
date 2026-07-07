<?php

use App\Http\Controllers\EntradasController;
use App\Http\Controllers\ReportEnterController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\Auth\LoginController;
use App\Models\Report_enter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\ReportRfmController;
use App\Http\Controllers\ReproteTxdController;
use App\Services\TbRetailService;
use Carbon\Carbon;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/', function () {
//     return view('reporte_venta');
// });

Route::get('/probar-python', [ReproteTxdController::class, 'ejecutarPython']);
Route::get('/test-python-version', [ReproteTxdController::class, 'testPythonVersion']);


Route::get('/inicio', [InicioController::class, 'index'])->name('inicio.index');
Route::get('/reporte', [ReportEnterController::class, 'index'])->name('entradas.index');
Route::get('/reportesb', [ReportEnterController::class, 'reportesb'])->name('reportesb.index');//reporte simplificado
//Route::post('/submit', [ReportEnterController::class, 'submitForm'])->name('form.submit');//original
Route::post('/submit', [ReportEnterController::class, 'nueva_tabla'])->name('form.submit');//
Route::post('/config_sucursales', [ReportEnterController::class, 'updateSucursales'])->name('form.config_sucursales');//
Route::get('/tabla', [ReportEnterController::class, 'vista_tabla'])->name('tabla');//original
Route::get('/rfm', [ReportRfmController::class, 'rfm'])->name('rfm');//original
Route::post('/submit_rfm', [ReportRfmController::class, 'submit_rfm'])->name('form.submit_rfm');//

Route::get('/reporte-ventas', [ReportEnterController::class, 'reporte_ventas'])->name('reporte_ventas');//original

Route::get('/getapi_conteo/{tipo}/{fecha_inicio}/{fecha_fin}', [ReportEnterController::class, 'getapi_conteo'])->name('form.getapi_conteo');//

Route::post('/getapi', [ReportEnterController::class, 'getapi'])->name('form.getapi');//real time
Route::get('/ver_mas2', [ReportEnterController::class, 'getapiprueba'])->name('form.getapiprueba');//

Route::get('/vermas', [ReportEnterController::class, 'vermas_tienda'])->name('form.vermas');
Route::post('/vermas_tienda', [ReportEnterController::class, 'vermas_tienda2'])->name('form.vermas2');

Route::get('/useradmin', [AdministradorController::class, 'index'])->name('admin.index');
Route::post('/get_usuario', [AdministradorController::class, 'get_usuario'])->name('form.usuario');
Route::post('/cambiar_permisos', [AdministradorController::class, 'cambiar'])->name('admin.cambiar');

Route::get('/', [LoginController::class,'index'] )->name('login.index');
//Route::post('/login', [LoginController::class,'login'] )->name('form.login');
Route::post('/login', [LoginController::class,'login2'] )->name('form.login');
Route::get('/cerrar', [LoginController::class,'cerrar'] )->name('cerrar');

Route::get('/reportetxd', [ReproteTxdController::class, 'reportetxd'])->name('reportetxd');;
Route::get('/cargar_documentos', [ReproteTxdController::class, 'cargar_documentos'])->name('cargar_documentos');
Route::post('/subir_documentos', [ReproteTxdController::class, 'subir_documentos'])->name('subir_documentos');
Route::post('/submit_txd', [ReproteTxdController::class, 'submit_txd'])->name('form.submit_txd');//


Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Nuevo proceso de dashboards (sobre automatizacion_pla_reporte_ventas)
Route::middleware('session.auth')->group(function () {
    Route::get('/dashboard/gerencial', [App\Http\Controllers\DashboardGerencialController::class, 'index'])->name('dashboard.gerencial');
    Route::get('/dashboard/ventas', [App\Http\Controllers\DashboardVentasController::class, 'index'])->name('dashboard.ventas');
    Route::get('/dashboard/ventas/tiendas', [App\Http\Controllers\DashboardVentasController::class, 'tiendas'])->name('dashboard.ventas.tiendas');
    Route::get('/dashboard/reporte', [App\Http\Controllers\DashboardReporteController::class, 'index'])->name('dashboard.reporte');
    Route::get('/dashboard/reporte/dia', [App\Http\Controllers\DashboardReporteController::class, 'dia'])->name('dashboard.reporte.dia');
    Route::get('/dashboard/reporte/detalle', [App\Http\Controllers\DashboardReporteController::class, 'detalle'])->name('dashboard.reporte.detalle');
    Route::get('/dashboard/reporte/pivot', [App\Http\Controllers\DashboardReporteController::class, 'pivot'])->name('dashboard.reporte.pivot');
    Route::get('/dashboard/ff-to', [App\Http\Controllers\DashboardFfToController::class, 'index'])->name('dashboard.ffto');
    Route::get('/dashboard/ff-to/data', [App\Http\Controllers\DashboardFfToController::class, 'data'])->name('dashboard.ffto.data');
});


Route::get('/probar-tbretail', function (TbRetailService $tbRetailService) {
    $resultadoMarca = $tbRetailService->guardarConteosTbRetail('marca');
    $resultadoTienda = $tbRetailService->guardarConteosTbRetail('tienda');

    return response()->json([
        'marca'   => $resultadoMarca,
        'tienda'  => $resultadoTienda,
        'status'  => 'ok'
    ]);
});


Route::get('/probar-tbretail-masivo', function (TbRetailService $tbRetailService) {

    set_time_limit(0);
    ini_set('memory_limit', '-1');

    $inicio = Carbon::create(2026, 06, 10);
    $fin    = Carbon::create(2026, 06, 15);

    $procesados = 0;
    $errores = [];

    while ($inicio->lte($fin)) {

        $fecha = $inicio->format('Y-m-d');

        try {

            // SOLO TIENDAS
            $tbRetailService->guardarConteosTbRetail('tienda', $fecha);

            $procesados++;

        } catch (\Exception $e) {

            $errores[] = [
                'fecha' => $fecha,
                'error' => $e->getMessage()
            ];
        }

        $inicio->addDay();

        // Evitar saturar la API
        sleep(1);
    }

    return response()->json([
        'status' => 'finalizado',
        'procesados' => $procesados,
        'errores' => $errores
    ]);
});