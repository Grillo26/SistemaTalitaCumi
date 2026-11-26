<?php

use App\Modules\CatalogoServicios\Http\Controllers\CategoriaTarifaController;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Categorías de tarifa — módulo 05, submódulo 02
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [CategoriaTarifaController::class, 'index'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::CATEGORIA_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/{id}', [CategoriaTarifaController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::CATEGORIA_VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [CategoriaTarifaController::class, 'store'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::CATEGORIA_EDIT, 'sanctum'))
        ->name('store');

    Route::patch('/{id}', [CategoriaTarifaController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::CATEGORIA_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/activar', [CategoriaTarifaController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::CATEGORIA_EDIT, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [CategoriaTarifaController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::CATEGORIA_EDIT, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [CategoriaTarifaController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::CATEGORIA_EDIT, 'sanctum'))
        ->name('destroy');
});
