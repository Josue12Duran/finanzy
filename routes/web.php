<?php

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/demo');

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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware(['auth', 'role:Administrador'])->group(function () {
    Route::get('/usuarios', function () {
        return view('usuarios.index');
    })->name('usuarios.index');

    Route::get('/bitacora', function () {
        return view('bitacora.index');
    })->name('bitacora.index');
});

Route::redirect('/home', '/dashboard')->name('home');

Route::get('/demo', function () {
    return view('demo');
})->name('demo');

Route::post('/demo', function (Request $request) {
    $request->validate([
        'descripcion' => ['required', 'string', 'max:255'],
        'monto' => ['required', 'numeric', 'min:0.01'],
        'fecha' => ['required', 'date'],
        'categoria_id' => ['required', 'integer', 'in:1,2,3,4'],
    ], [], [
        'descripcion' => 'descripción',
        'categoria_id' => 'categoría',
    ]);

    return redirect('/demo')->with('success', 'Registro de prueba validado correctamente (no se guardó en base de datos).');
})->name('demo.store');
