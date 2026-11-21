<?php

use App\Modules\Pacientes\Http\Controllers\Consentimiento\ConsentimientoController;
use App\Modules\Pacientes\Http\Controllers\Consentimiento\ConsentimientoTipoController;
use App\Modules\Pacientes\Support\ConsentimientoPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas de consentimientos (04.03 Fase B)
|--------------------------------------------------------------------------
|
| El catálogo es global y solo lo administra Superadmin. Los consentimientos
| cuelgan de una ficha concreta y se auditan como consulta sensible.
|
*/

Route::middleware('auth:sanctum')->prefix('ficha-ingreso/consentimientos/tipos')->group(function (): void {
    Route::get('/', [ConsentimientoTipoController::class, 'index'])
        ->middleware(PermissionMiddleware::using(ConsentimientoPermission::CATALOG, 'sanctum'))
        ->name('tipos.index');

    Route::post('/', [ConsentimientoTipoController::class, 'store'])
        ->middleware(PermissionMiddleware::using(ConsentimientoPermission::CATALOG, 'sanctum'))
        ->name('tipos.store');

    Route::patch('/{tipoId}', [ConsentimientoTipoController::class, 'update'])
        ->whereNumber('tipoId')
        ->middleware(PermissionMiddleware::using(ConsentimientoPermission::CATALOG, 'sanctum'))
        ->name('tipos.update');

    Route::delete('/{tipoId}', [ConsentimientoTipoController::class, 'destroy'])
        ->whereNumber('tipoId')
        ->middleware(PermissionMiddleware::using(ConsentimientoPermission::CATALOG, 'sanctum'))
        ->name('tipos.destroy');
});

Route::middleware('auth:sanctum')
    ->prefix('pacientes/{id}/ficha-ingreso/{fichaId}/consentimientos')
    ->group(function (): void {
        Route::get('/', [ConsentimientoController::class, 'index'])
            ->whereNumber('id')
            ->whereNumber('fichaId')
            ->middleware(PermissionMiddleware::using(ConsentimientoPermission::VIEW, 'sanctum'))
            ->name('index');

        Route::post('/', [ConsentimientoController::class, 'store'])
            ->whereNumber('id')
            ->whereNumber('fichaId')
            ->middleware(PermissionMiddleware::using(ConsentimientoPermission::REGISTER, 'sanctum'))
            ->name('store');

        Route::post('/{consentimientoId}/revocar', [ConsentimientoController::class, 'revoke'])
            ->whereNumber('id')
            ->whereNumber('fichaId')
            ->whereNumber('consentimientoId')
            ->middleware(PermissionMiddleware::using(ConsentimientoPermission::REVOKE, 'sanctum'))
            ->name('revoke');

        Route::delete('/{consentimientoId}', [ConsentimientoController::class, 'destroy'])
            ->whereNumber('id')
            ->whereNumber('fichaId')
            ->whereNumber('consentimientoId')
            ->middleware(PermissionMiddleware::using(ConsentimientoPermission::REVOKE, 'sanctum'))
            ->name('destroy');
    });
