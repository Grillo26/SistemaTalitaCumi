<?php

use App\Modules\Organizacion\Http\Controllers\ParameterController;
use App\Modules\Organizacion\Support\ConfiguracionPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del submódulo Configuración general y parámetros
|--------------------------------------------------------------------------
|
| El prefijo /api y el middleware api se agregan desde
| OrganizacionServiceProvider. Los parámetros son globales: no hay alcance por
| sucursal; el único control es por permiso. No existen POST de alta, DELETE ni
| activación/desactivación: el catálogo lo define un seeder versionado.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::prefix('parametros')->name('parametros.')->group(function (): void {
        Route::get('/', [ParameterController::class, 'index'])
            ->middleware(PermissionMiddleware::using(
                [ConfiguracionPermission::VIEW, ConfiguracionPermission::VIEW_OPERATIVES],
                'sanctum',
            ))
            ->name('index');

        Route::get('/{id}', [ParameterController::class, 'show'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(
                [ConfiguracionPermission::VIEW, ConfiguracionPermission::VIEW_OPERATIVES],
                'sanctum',
            ))
            ->name('show');

        Route::patch('/{id}', [ParameterController::class, 'update'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(ConfiguracionPermission::EDIT, 'sanctum'))
            ->name('update');

        Route::post('/{id}/restaurar', [ParameterController::class, 'restore'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(ConfiguracionPermission::RESTORE, 'sanctum'))
            ->name('restore');

        Route::get('/{id}/historial', [ParameterController::class, 'history'])
            ->whereNumber('id')
            ->middleware(PermissionMiddleware::using(ConfiguracionPermission::VIEW, 'sanctum'))
            ->name('history');
    });

    Route::post('/logo', [ParameterController::class, 'uploadLogo'])
        ->middleware(PermissionMiddleware::using(ConfiguracionPermission::EDIT, 'sanctum'))
        ->name('logo');
});
