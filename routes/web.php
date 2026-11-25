<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\CopiaSeguridadController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportarReporteController;
use App\Http\Controllers\MovimientoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\UserController;
use App\Http\Requests\StoreDemoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

// Sin sesión, el middleware auth del panel lleva al inicio de sesión.
Route::get('/', fn (): RedirectResponse => to_route('dashboard'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/password/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');

    Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
});

Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/dashboard', DashboardController::class)->middleware('auth')->name('dashboard');

// Reportes: pantalla, PDF y CSV comparten los filtros de GenerarReporteRequest.
Route::middleware('auth')->prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/', [ReporteController::class, 'index'])->name('index');
    Route::get('/pdf', [ReporteController::class, 'pdf'])->name('pdf');
    Route::get('/csv', [ExportarReporteController::class, 'csv'])->name('csv');
});

// Categorías y movimientos financieros: accesibles a todos los usuarios autenticados.
Route::middleware('auth')->group(function () {
    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    Route::get('/ingresos', [MovimientoController::class, 'ingresos'])->name('ingresos.index');
    Route::get('/gastos', [MovimientoController::class, 'gastos'])->name('gastos.index');
    Route::get('/movimientos/registrar', [MovimientoController::class, 'create'])->name('movimientos.create');
    Route::post('/movimientos', [MovimientoController::class, 'store'])->name('movimientos.store');
    Route::put('/movimientos/{movimiento}', [MovimientoController::class, 'update'])->name('movimientos.update');
    Route::delete('/movimientos/{movimiento}', [MovimientoController::class, 'destroy'])->name('movimientos.destroy');
    Route::patch('/movimientos/{movimiento}/activar', [MovimientoController::class, 'activar'])->name('movimientos.activar');
});

Route::middleware(['auth', 'role:Administrador'])->group(function () {
    Route::resource('usuarios', UserController::class)->except(['show', 'destroy']);
    Route::patch('usuarios/{usuario}/estado', [UserController::class, 'toggleStatus'])->name('usuarios.toggle-status');

    Route::get('/bitacora', [ActivityLogController::class, 'index'])->name('bitacora.index');
    Route::get('/copias-seguridad', [CopiaSeguridadController::class, 'index'])->name('copias-seguridad.index');
    Route::post('/copias-seguridad/descargar', [CopiaSeguridadController::class, 'download'])->name('copias-seguridad.download');
});

Route::redirect('/home', '/dashboard')->name('home');

// Catálogo de componentes con datos ficticios: solo para usuarios autenticados.
Route::middleware('auth')->group(function () {
    Route::get('/demo', function () {
        return view('demo');
    })->name('demo');

    Route::post('/demo', function (StoreDemoRequest $request) {
        return redirect('/demo')->with('success', 'Registro de prueba validado correctamente (no se guardó en base de datos).');
    })->name('demo.store');
});
