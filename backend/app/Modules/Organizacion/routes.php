<?php

use App\Modules\Organizacion\Http\Controllers\BranchController;
use App\Modules\Organizacion\Http\Controllers\BranchStatusController;
use App\Modules\Organizacion\Support\BranchPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del submódulo Organización y sucursales
|--------------------------------------------------------------------------
|
| El prefijo /api y el middleware api se agregan desde
| OrganizacionServiceProvider. Todas las rutas exigen Sanctum y un permiso
| concreto. La autorización por sucursal se valida en Queries y Actions.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [BranchController::class, 'index'])
        ->middleware(PermissionMiddleware::using(BranchPermission::VIEW, 'sanctum'))
        ->name('index');

    Route::get('/{id}', [BranchController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(BranchPermission::VIEW, 'sanctum'))
        ->name('show');

    Route::post('/', [BranchController::class, 'store'])
        ->middleware(PermissionMiddleware::using(BranchPermission::CREATE, 'sanctum'))
        ->name('store');

    Route::patch('/{id}', [BranchController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(
            [BranchPermission::EDIT, BranchPermission::EDIT_CONTACT],
            'sanctum',
        ))
        ->name('update');

    Route::post('/{id}/activar', [BranchStatusController::class, 'activate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(BranchPermission::CHANGE_STATUS, 'sanctum'))
        ->name('activate');

    Route::post('/{id}/desactivar', [BranchStatusController::class, 'deactivate'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(BranchPermission::CHANGE_STATUS, 'sanctum'))
        ->name('deactivate');

    Route::delete('/{id}', [BranchController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(BranchPermission::CHANGE_STATUS, 'sanctum'))
        ->name('destroy');
});
