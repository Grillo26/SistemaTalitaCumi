<?php

use App\Modules\CatalogoServicios\Http\Controllers\TipoServicioController;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Tipos de servicio — módulo 05, submódulo 01
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [TipoServicioController::class, 'index'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/{id}', [TipoServicioController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [TipoServicioController::class, 'store'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('store');

    Route::patch('/{id}', [TipoServicioController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/activar', [TipoServicioController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [TipoServicioController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [TipoServicioController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('destroy');
});
