<?php

use App\Modules\CatalogoServicios\Http\Controllers\AreaController;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Áreas — módulo 05, submódulo 01
|--------------------------------------------------------------------------
|
| El prefijo /api/catalogo-servicios se agrega en el ServiceProvider.
| Todas las rutas exigen Sanctum y permiso; el catálogo es global.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [AreaController::class, 'index'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::AREA_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/{id}', [AreaController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::AREA_VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [AreaController::class, 'store'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::AREA_EDIT, 'sanctum'))
        ->name('store');

    Route::patch('/{id}', [AreaController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::AREA_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/activar', [AreaController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::AREA_EDIT, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [AreaController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::AREA_EDIT, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [AreaController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::AREA_EDIT, 'sanctum'))
        ->name('destroy');
});
