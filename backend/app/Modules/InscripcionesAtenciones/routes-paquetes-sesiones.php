<?php

use App\Modules\InscripcionesAtenciones\Http\Controllers\PaqueteComprobanteController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\PaqueteConsumoController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\PaqueteExportController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\PaquetePagoController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\PaqueteSesionesController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\PaqueteTurnosController;
use App\Modules\InscripcionesAtenciones\Support\PaquetePermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Paquetes de sesiones individuales — módulo 06, submódulo 02
|--------------------------------------------------------------------------
*/

Route::get('/', [PaqueteSesionesController::class, 'index'])
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('index');

Route::get('/opciones', [PaqueteSesionesController::class, 'opciones'])
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('opciones');

Route::get('/monto-esperado', [PaqueteSesionesController::class, 'previewMonto'])
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('monto-esperado');

Route::get('/exportar', [PaqueteExportController::class, 'export'])
    ->middleware(PermissionMiddleware::using(PaquetePermission::EXPORT, 'sanctum'))
    ->name('exportar');

Route::post('/', [PaqueteSesionesController::class, 'store'])
    ->middleware(PermissionMiddleware::using(PaquetePermission::CREATE, 'sanctum'))
    ->name('store');

Route::get('/{id}/renovacion', [PaqueteSesionesController::class, 'renovacionPreview'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::RENEW, 'sanctum'))
    ->name('renovacion-preview');

Route::get('/{id}/consumo', [PaqueteConsumoController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('consumo');

Route::get('/{id}/turnos-futuros', [PaqueteTurnosController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('turnos-futuros');

Route::get('/{id}/pago', [PaquetePagoController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('pago.show');

Route::post('/{id}/pago', [PaquetePagoController::class, 'store'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('pago.store');

Route::get('/{id}/comprobante', [PaqueteComprobanteController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('comprobante');

Route::get('/{id}/comprobante/pdf', [PaqueteComprobanteController::class, 'pdf'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('comprobante.pdf');

Route::get('/{id}', [PaqueteSesionesController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::VIEW, 'sanctum'))
    ->name('show');

Route::patch('/{id}', [PaqueteSesionesController::class, 'update'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::EDIT, 'sanctum'))
    ->name('update');

Route::post('/{id}/renovar', [PaqueteSesionesController::class, 'renew'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::RENEW, 'sanctum'))
    ->name('renew');

Route::post('/{id}/retirar', [PaqueteSesionesController::class, 'withdraw'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::WITHDRAW, 'sanctum'))
    ->name('withdraw');

Route::delete('/{id}', [PaqueteSesionesController::class, 'destroy'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PaquetePermission::WITHDRAW, 'sanctum'))
    ->name('destroy');
