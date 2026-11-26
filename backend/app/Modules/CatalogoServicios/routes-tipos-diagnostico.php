<?php

use App\Modules\CatalogoServicios\Http\Controllers\TipoDiagnosticoController;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Tipos de diagnóstico — módulo 05, submódulo 03
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [TipoDiagnosticoController::class, 'index'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_VIEW, 'sanctum'))
        ->name('index');

    Route::post('/', [TipoDiagnosticoController::class, 'store'])
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_EDIT, 'sanctum'))
        ->name('store');

    Route::get('/{id}/especialidades-requeridas', [TipoDiagnosticoController::class, 'requirements'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_VIEW, 'sanctum'))
        ->name('requirements');

    Route::get('/{id}', [TipoDiagnosticoController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_VIEW, 'sanctum'))
        ->name('show');

    Route::patch('/{id}', [TipoDiagnosticoController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/activar', [TipoDiagnosticoController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_EDIT, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [TipoDiagnosticoController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_EDIT, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [TipoDiagnosticoController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_EDIT, 'sanctum'))
        ->name('destroy');
});
