<?php

use App\Modules\Pacientes\Http\Controllers\Observacion\ObservacionController;
use App\Modules\Pacientes\Support\ObservacionPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas de observaciones fechadas (04.03 Fase C)
|--------------------------------------------------------------------------
|
| Solo inserción. El texto es sensible y la consulta se audita.
|
*/

Route::middleware('auth:sanctum')
    ->prefix('pacientes/{id}/ficha-ingreso/{fichaId}/observaciones')
    ->group(function (): void {
        Route::get('/', [ObservacionController::class, 'index'])
            ->whereNumber('id')
            ->whereNumber('fichaId')
            ->middleware(PermissionMiddleware::using(ObservacionPermission::VIEW, 'sanctum'))
            ->name('index');

        Route::post('/', [ObservacionController::class, 'store'])
            ->whereNumber('id')
            ->whereNumber('fichaId')
            ->middleware(PermissionMiddleware::using(ObservacionPermission::ADD, 'sanctum'))
            ->name('store');

        Route::delete('/{observacionId}', [ObservacionController::class, 'destroy'])
            ->whereNumber('id')
            ->whereNumber('fichaId')
            ->whereNumber('observacionId')
            ->middleware(PermissionMiddleware::using(ObservacionPermission::ADD, 'sanctum'))
            ->name('destroy');
    });
