<?php

use App\Modules\Seguridad\Http\Controllers\UserController;
use App\Modules\Seguridad\Http\Controllers\UserHistoryController;
use App\Modules\Seguridad\Http\Controllers\UserPasswordController;
use App\Modules\Seguridad\Http\Controllers\UserSessionController;
use App\Modules\Seguridad\Http\Controllers\UserStatusController;
use App\Modules\Seguridad\Http\Controllers\UserUnlockController;
use App\Modules\Seguridad\Support\SecurityPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del pilar de usuarios (02 / Fase 2)
|--------------------------------------------------------------------------
|
| Prefijo /api/seguridad/usuarios y middleware api desde
| SeguridadServiceProvider. Todas las rutas exigen Sanctum + permiso.
| El alcance por sucursal se valida en Queries y Actions.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [UserController::class, 'index'])
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/opciones', [UserController::class, 'options'])
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_VIEW, 'sanctum'))
        ->name('options');

    Route::get('/{id}/historial', [UserHistoryController::class, 'index'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_VIEW, 'sanctum'))
        ->name('history');

    Route::get('/{id}', [UserController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [UserController::class, 'store'])
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_CREATE, 'sanctum'))
        ->name('store');

    Route::patch('/{id}', [UserController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/activar', [UserStatusController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_CHANGE_STATUS, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [UserStatusController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_CHANGE_STATUS, 'sanctum'))
        ->name('deactivate');

    Route::post('/{id}/resetear-password', [UserPasswordController::class, 'reset'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_RESET_PASSWORD, 'sanctum'))
        ->name('reset-password');

    Route::post('/{id}/desbloquear', UserUnlockController::class)
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_UNLOCK, 'sanctum'))
        ->name('unlock');

    Route::post('/{id}/cerrar-sesiones', [UserSessionController::class, 'close'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_CLOSE_SESSIONS, 'sanctum'))
        ->name('close-sessions');

    Route::delete('/{id}', [UserController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::USER_CHANGE_STATUS, 'sanctum'))
        ->name('destroy');
});
