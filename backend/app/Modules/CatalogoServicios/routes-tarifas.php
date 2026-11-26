<?php

use App\Modules\CatalogoServicios\Http\Controllers\TarifaController;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Tarifas — módulo 05, submódulo 02
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [TarifaController::class, 'index'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TARIFA_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/aplicable', [TarifaController::class, 'aplicable'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TARIFA_VIEW, 'sanctum'))
        ->name('aplicable');

    Route::get('/{id}', [TarifaController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TARIFA_VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [TarifaController::class, 'store'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TARIFA_EDIT, 'sanctum'))
        ->name('store');

    Route::patch('/{id}', [TarifaController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TARIFA_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/cerrar-vigencia', [TarifaController::class, 'close'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TARIFA_EDIT, 'sanctum'))
        ->name('close');

    Route::delete('/{id}', [TarifaController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TARIFA_EDIT, 'sanctum'))
        ->name('destroy');
});
