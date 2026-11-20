<?php

use App\Modules\Organizacion\Http\Controllers\AmbienteController;
use App\Modules\Organizacion\Http\Controllers\AreaController;
use App\Modules\Organizacion\Http\Controllers\SalaController;
use App\Modules\Organizacion\Http\Controllers\TipoSalaController;
use App\Modules\Organizacion\Http\Controllers\TurnoCatalogoController;
use App\Modules\Organizacion\Support\SalasAmbientesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del submódulo Salas y ambientes
|--------------------------------------------------------------------------
|
| El prefijo /api y el middleware api se agregan desde
| OrganizacionServiceProvider. Todas las rutas exigen Sanctum y un permiso
| concreto. El alcance por sucursal se valida en Queries y Actions.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/areas', [AreaController::class, 'index'])
        ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::CATALOG_VIEW, 'sanctum'))
        ->name('areas.index');

    Route::prefix('tipos-sala')->name('tipos-sala.')->group(function (): void {
        Route::get('/', [TipoSalaController::class, 'index'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::CATALOG_VIEW, 'sanctum'))
            ->name('index');

        Route::get('/{id}', [TipoSalaController::class, 'show'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::CATALOG_VIEW, 'sanctum'))
            ->name('show');

        Route::post('/', [TipoSalaController::class, 'store'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TIPO_SALA_ADMIN, 'sanctum'))
            ->name('store');

        Route::patch('/{id}', [TipoSalaController::class, 'update'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TIPO_SALA_ADMIN, 'sanctum'))
            ->name('update');

        Route::post('/{id}/activar', [TipoSalaController::class, 'activate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TIPO_SALA_ADMIN, 'sanctum'))
            ->name('activate');

        Route::post('/{id}/desactivar', [TipoSalaController::class, 'deactivate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TIPO_SALA_ADMIN, 'sanctum'))
            ->name('deactivate');

        Route::delete('/{id}', [TipoSalaController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TIPO_SALA_ADMIN, 'sanctum'))
            ->name('destroy');
    });

    Route::prefix('turnos-catalogo')->name('turnos-catalogo.')->group(function (): void {
        Route::get('/', [TurnoCatalogoController::class, 'index'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::CATALOG_VIEW, 'sanctum'))
            ->name('index');

        Route::get('/{id}', [TurnoCatalogoController::class, 'show'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::CATALOG_VIEW, 'sanctum'))
            ->name('show');

        Route::post('/', [TurnoCatalogoController::class, 'store'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TURNO_ADMIN, 'sanctum'))
            ->name('store');

        Route::patch('/{id}', [TurnoCatalogoController::class, 'update'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TURNO_ADMIN, 'sanctum'))
            ->name('update');

        Route::post('/{id}/activar', [TurnoCatalogoController::class, 'activate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TURNO_ADMIN, 'sanctum'))
            ->name('activate');

        Route::post('/{id}/desactivar', [TurnoCatalogoController::class, 'deactivate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TURNO_ADMIN, 'sanctum'))
            ->name('deactivate');

        Route::delete('/{id}', [TurnoCatalogoController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::TURNO_ADMIN, 'sanctum'))
            ->name('destroy');
    });

    Route::prefix('salas')->name('salas.')->group(function (): void {
        Route::get('/', [SalaController::class, 'index'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::SALA_VIEW, 'sanctum'))
            ->name('index');

        Route::get('/{id}', [SalaController::class, 'show'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::SALA_VIEW, 'sanctum'))
            ->name('show');

        Route::post('/', [SalaController::class, 'store'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::SALA_CREATE, 'sanctum'))
            ->name('store');

        Route::patch('/{id}', [SalaController::class, 'update'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(
                [SalasAmbientesPermission::SALA_EDIT_STRUCTURE, SalasAmbientesPermission::SALA_EDIT_DATA],
                'sanctum',
            ))
            ->name('update');

        Route::post('/{id}/activar', [SalaController::class, 'activate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::SALA_CHANGE_STATUS, 'sanctum'))
            ->name('activate');

        Route::post('/{id}/desactivar', [SalaController::class, 'deactivate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::SALA_CHANGE_STATUS, 'sanctum'))
            ->name('deactivate');

        Route::delete('/{id}', [SalaController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::SALA_CHANGE_STATUS, 'sanctum'))
            ->name('destroy');
    });

    Route::prefix('ambientes')->name('ambientes.')->group(function (): void {
        Route::get('/', [AmbienteController::class, 'index'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::AMBIENTE_VIEW, 'sanctum'))
            ->name('index');

        Route::get('/{id}', [AmbienteController::class, 'show'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::AMBIENTE_VIEW, 'sanctum'))
            ->name('show');

        Route::post('/', [AmbienteController::class, 'store'])
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::AMBIENTE_CREATE, 'sanctum'))
            ->name('store');

        Route::patch('/{id}', [AmbienteController::class, 'update'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(
                [SalasAmbientesPermission::AMBIENTE_EDIT_STRUCTURE, SalasAmbientesPermission::AMBIENTE_EDIT_DATA],
                'sanctum',
            ))
            ->name('update');

        Route::post('/{id}/activar', [AmbienteController::class, 'activate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::AMBIENTE_CHANGE_STATUS, 'sanctum'))
            ->name('activate');

        Route::post('/{id}/desactivar', [AmbienteController::class, 'deactivate'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::AMBIENTE_CHANGE_STATUS, 'sanctum'))
            ->name('deactivate');

        Route::delete('/{id}', [AmbienteController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(SalasAmbientesPermission::AMBIENTE_CHANGE_STATUS, 'sanctum'))
            ->name('destroy');
    });
});
