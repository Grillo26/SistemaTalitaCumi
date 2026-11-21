<?php

use App\Modules\Profesionales\Http\Controllers\Especialidades\EspecialidadController;
use App\Modules\Profesionales\Http\Controllers\Especialidades\EspecialidadStatusController;
use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del catálogo de especialidades (03 / Fase 1)
|--------------------------------------------------------------------------
|
| El prefijo /api/profesionales y el middleware api se agregan desde
| ProfesionalesServiceProvider. El catálogo es global; solo cambia el permiso.
|
*/

Route::middleware('auth:sanctum')->prefix('especialidades')->group(function (): void {
    Route::get('/', [EspecialidadController::class, 'index'])
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::SPECIALTY_VIEW, 'sanctum'))
        ->name('index');

    Route::post('/', [EspecialidadController::class, 'store'])
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::SPECIALTY_ADMIN, 'sanctum'))
        ->name('store');

    Route::get('/{id}', [EspecialidadController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::SPECIALTY_VIEW, 'sanctum'))
        ->name('show');

    Route::patch('/{id}', [EspecialidadController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::SPECIALTY_ADMIN, 'sanctum'))
        ->name('update');

    Route::post('/{id}/activar', [EspecialidadStatusController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::SPECIALTY_ADMIN, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [EspecialidadStatusController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::SPECIALTY_ADMIN, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [EspecialidadController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::SPECIALTY_ADMIN, 'sanctum'))
        ->name('destroy');
});
