<?php

use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Http\Controllers\PagoController;
use App\Modules\Pagos\Http\Controllers\PagoEntidadController;
use App\Modules\Pagos\Http\Controllers\PagoExportController;
use App\Modules\Pagos\Http\Controllers\PagoOpcionesController;
use App\Modules\Pagos\Support\PagoPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Registro de pagos y saldos — módulo 08, submódulo 01
|--------------------------------------------------------------------------
|
| Prefijo /api/pagos. Las rutas estáticas van antes que {id}.
| El alcance por sucursal se valida en Queries y Actions.
|
*/

Route::get('/', [PagoController::class, 'index'])
    ->middleware(PermissionMiddleware::using(PagoPermission::VIEW, 'sanctum'))
    ->name('index');

Route::get('/opciones', [PagoOpcionesController::class, 'show'])
    ->middleware(PermissionMiddleware::using(PagoPermission::VIEW, 'sanctum'))
    ->name('opciones');

Route::get('/buscar-entidades', [PagoEntidadController::class, 'buscar'])
    ->middleware(PermissionMiddleware::using(PagoPermission::REGISTER, 'sanctum'))
    ->name('buscar-entidades');

Route::get('/exportar', [PagoExportController::class, 'export'])
    ->middleware(PermissionMiddleware::using(PagoPermission::EXPORT, 'sanctum'))
    ->name('exportar');

Route::get('/entidades/{tipo}/{id}/historial', [PagoEntidadController::class, 'historial'])
    ->whereIn('tipo', TipoEntidadPago::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PagoPermission::VIEW, 'sanctum'))
    ->name('entidades.historial');

Route::post('/', [PagoController::class, 'store'])
    ->middleware(PermissionMiddleware::using(PagoPermission::REGISTER, 'sanctum'))
    ->name('store');

Route::get('/{id}', [PagoController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PagoPermission::VIEW, 'sanctum'))
    ->name('show');

Route::delete('/{id}', [PagoController::class, 'destroy'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PagoPermission::VIEW, 'sanctum'))
    ->name('destroy');
