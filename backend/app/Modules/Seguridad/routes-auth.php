<?php

use App\Modules\Seguridad\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de autenticación (02 / Fase 1)
|--------------------------------------------------------------------------
|
| El prefijo /api y el middleware api se agregan desde
| SeguridadServiceProvider. El login es público; el resto exige Sanctum.
|
*/

Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:20,1')
    ->name('login');

Route::middleware('auth:sanctum')->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/me', [AuthController::class, 'me'])->name('me');
    Route::post('/cambiar-password', [AuthController::class, 'changePassword'])
        ->name('change-password');
});
