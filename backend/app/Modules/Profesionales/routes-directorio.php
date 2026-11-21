<?php

use App\Modules\Profesionales\Http\Controllers\Directorio\ProfesionalController;
use App\Modules\Profesionales\Http\Controllers\Directorio\ProfesionalStatusController;
use App\Modules\Profesionales\Http\Controllers\Directorio\ProfesionalSucursalController;
use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del directorio de profesionales (03 / Fase 1)
|--------------------------------------------------------------------------
|
| El prefijo /api/profesionales y el middleware api se agregan desde
| ProfesionalesServiceProvider. El alcance por sucursal se valida en
| Queries y Actions.
|
*/

Route::middleware('auth:sanctum')->prefix('profesionales')->group(function (): void {
    Route::get('/', [ProfesionalController::class, 'index'])
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/opciones', [ProfesionalController::class, 'options'])
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_VIEW, 'sanctum'))
        ->name('options');

    Route::post('/', [ProfesionalController::class, 'store'])
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_CREATE, 'sanctum'))
        ->name('store');

    Route::get('/{id}', [ProfesionalController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_VIEW, 'sanctum'))
        ->name('show');

    Route::patch('/{id}', [ProfesionalController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(
            [ProfesionalesPermission::PROFESSIONAL_EDIT_DATA, ProfesionalesPermission::PROFESSIONAL_EDIT_CONTACT],
            'sanctum',
        ))
        ->name('update');

    Route::post('/{id}/activar', [ProfesionalStatusController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_CHANGE_STATUS, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [ProfesionalStatusController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_CHANGE_STATUS, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [ProfesionalController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_CHANGE_STATUS, 'sanctum'))
        ->name('destroy');

    Route::get('/{id}/sucursales', [ProfesionalSucursalController::class, 'index'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_VIEW, 'sanctum'))
        ->name('sucursales.index');

    Route::post('/{id}/sucursales', [ProfesionalSucursalController::class, 'store'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN, 'sanctum'))
        ->name('sucursales.store');

    Route::post('/{id}/sucursales/{sucursalId}/activar', [ProfesionalSucursalController::class, 'activate'])
        ->whereNumber('id')
        ->whereNumber('sucursalId')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN, 'sanctum'))
        ->name('sucursales.activate');

    Route::post('/{id}/sucursales/{sucursalId}/desactivar', [ProfesionalSucursalController::class, 'deactivate'])
        ->whereNumber('id')
        ->whereNumber('sucursalId')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN, 'sanctum'))
        ->name('sucursales.deactivate');

    Route::delete('/{id}/sucursales/{sucursalId}', [ProfesionalSucursalController::class, 'destroy'])
        ->whereNumber('id')
        ->whereNumber('sucursalId')
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN, 'sanctum'))
        ->name('sucursales.destroy');
});
