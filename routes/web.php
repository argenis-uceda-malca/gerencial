<?php

use App\Http\Controllers\EntradasController;
use App\Http\Controllers\ReportEnterController;
use App\Http\Controllers\AdministradorController;
use App\Http\Controllers\Auth\LoginController;
use App\Models\Report_enter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

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
Route::get('/reporte', [ReportEnterController::class, 'index'])->name('entradas.index');
Route::post('/submit', [ReportEnterController::class, 'submitForm'])->name('form.submit');

Route::get('/useradmin', [AdministradorController::class, 'index'])->name('admin.index');
Route::post('/cambiar_permisos', [AdministradorController::class, 'cambiar'])->name('admin.cambiar');

Route::get('/', [LoginController::class,'index'] )->name('login.index');
Route::post('/login', [LoginController::class,'login'] )->name('form.login');

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
