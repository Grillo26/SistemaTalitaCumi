<?php

use App\Modules\Seguridad\Http\Controllers\RoleController;
use App\Modules\Seguridad\Support\SecurityPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del pilar de roles y permisos (02 / Fase 3)
|--------------------------------------------------------------------------
|
| Prefijo /api/seguridad/roles y middleware api desde
| SeguridadServiceProvider. Lectura con rol.ver; edición con
| rol.editar_permisos. Los roles no se crean ni se eliminan.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [RoleController::class, 'index'])
        ->middleware(PermissionMiddleware::using(SecurityPermission::ROLE_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/{id}', [RoleController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::ROLE_VIEW, 'sanctum'))
        ->name('show');

    Route::get('/{id}/historial', [RoleController::class, 'history'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::ROLE_VIEW, 'sanctum'))
        ->name('history');

    Route::put('/{id}/permisos', [RoleController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::ROLE_EDIT_PERMISSIONS, 'sanctum'))
        ->name('update-permissions');

    Route::delete('/{id}', [RoleController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::ROLE_EDIT_PERMISSIONS, 'sanctum'))
        ->name('destroy');
});
