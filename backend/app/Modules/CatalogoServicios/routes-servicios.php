<?php

use App\Modules\CatalogoServicios\Http\Controllers\ServicioController;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Servicios — módulo 05, submódulo 01
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [ServicioController::class, 'index'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/{id}', [ServicioController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [ServicioController::class, 'store'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('store');

    Route::patch('/{id}', [ServicioController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/activar', [ServicioController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [ServicioController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [ServicioController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::SERVICIO_EDIT, 'sanctum'))
        ->name('destroy');
});
