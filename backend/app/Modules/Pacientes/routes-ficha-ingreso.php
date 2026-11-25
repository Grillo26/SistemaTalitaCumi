<?php

use App\Modules\Pacientes\Http\Controllers\Ficha\FichaIngresoController;
use App\Modules\Pacientes\Support\FichaIngresoPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas de la ficha de ingreso (04.03 Fase A)
|--------------------------------------------------------------------------
|
| El prefijo /api/pacientes y el middleware api se agregan desde
| PacientesServiceProvider. La lectura de la ficha y de sus revisiones se
| audita con AuditRecorderContract.
|
*/

Route::middleware('auth:sanctum')->prefix('pacientes/{id}/ficha-ingreso')->group(function (): void {
    Route::get('/', [FichaIngresoController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [FichaIngresoController::class, 'store'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::CREATE, 'sanctum'))
        ->name('store');

    Route::get('/{fichaId}/revisiones', [FichaIngresoController::class, 'revisions'])
        ->whereNumber('id')
        ->whereNumber('fichaId')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::VIEW, 'sanctum'))
        ->name('revisions');

    Route::get('/{fichaId}', [FichaIngresoController::class, 'showOne'])
        ->whereNumber('id')
        ->whereNumber('fichaId')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::VIEW, 'sanctum'))
        ->name('show-one');

    Route::patch('/{fichaId}', [FichaIngresoController::class, 'update'])
        ->whereNumber('id')
        ->whereNumber('fichaId')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{fichaId}/completar', [FichaIngresoController::class, 'complete'])
        ->whereNumber('id')
        ->whereNumber('fichaId')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::EDIT, 'sanctum'))
        ->name('complete');

    Route::post('/{fichaId}/archivar', [FichaIngresoController::class, 'archive'])
        ->whereNumber('id')
        ->whereNumber('fichaId')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::ARCHIVE, 'sanctum'))
        ->name('archive');

    Route::post('/{fichaId}/reactivar', [FichaIngresoController::class, 'reactivate'])
        ->whereNumber('id')
        ->whereNumber('fichaId')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::REACTIVATE, 'sanctum'))
        ->name('reactivate');

    Route::delete('/{fichaId}', [FichaIngresoController::class, 'destroy'])
        ->whereNumber('id')
        ->whereNumber('fichaId')
        ->middleware(PermissionMiddleware::using(FichaIngresoPermission::EDIT, 'sanctum'))
        ->name('destroy');
});
