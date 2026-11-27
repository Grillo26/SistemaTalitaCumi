<?php

use App\Modules\InscripcionesAtenciones\Http\Controllers\InscripcionAsistenciaController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\InscripcionComprobanteController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\InscripcionExportController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\InscripcionPagoController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\InscripcionSalaGrupalController;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Inscripción a salas grupales — módulo 06, submódulo 01
|--------------------------------------------------------------------------
|
| El prefijo /api/inscripciones-atenciones y el middleware api se agregan
| desde InscripcionesAtencionesServiceProvider. El alcance por sucursal se
| valida en Queries y Actions.
|
*/

Route::get('/', [InscripcionSalaGrupalController::class, 'index'])
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('index');

Route::get('/monto-esperado', [InscripcionSalaGrupalController::class, 'previewMonto'])
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('monto-esperado');

Route::get('/exportar', [InscripcionExportController::class, 'export'])
    ->middleware(PermissionMiddleware::using(InscripcionPermission::EXPORT, 'sanctum'))
    ->name('exportar');

Route::post('/', [InscripcionSalaGrupalController::class, 'store'])
    ->middleware(PermissionMiddleware::using(InscripcionPermission::CREATE, 'sanctum'))
    ->name('store');

Route::get('/{id}/renovacion', [InscripcionSalaGrupalController::class, 'renovacionPreview'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::RENEW, 'sanctum'))
    ->name('renovacion-preview');

Route::get('/{id}/pago', [InscripcionPagoController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('pago.show');

Route::post('/{id}/pago', [InscripcionPagoController::class, 'store'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('pago.store');

Route::get('/{id}/asistencia', [InscripcionAsistenciaController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('asistencia');

Route::get('/{id}/turnos-futuros', [InscripcionAsistenciaController::class, 'turnosFuturos'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::WITHDRAW, 'sanctum'))
    ->name('turnos-futuros');

Route::get('/{id}/comprobante', [InscripcionComprobanteController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('comprobante');

Route::get('/{id}/comprobante/pdf', [InscripcionComprobanteController::class, 'pdf'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('comprobante.pdf');

Route::get('/{id}', [InscripcionSalaGrupalController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::VIEW, 'sanctum'))
    ->name('show');

Route::patch('/{id}', [InscripcionSalaGrupalController::class, 'update'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::EDIT, 'sanctum'))
    ->name('update');

Route::post('/{id}/renovar', [InscripcionSalaGrupalController::class, 'renew'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::RENEW, 'sanctum'))
    ->name('renew');

Route::post('/{id}/retirar', [InscripcionSalaGrupalController::class, 'withdraw'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::WITHDRAW, 'sanctum'))
    ->name('withdraw');

Route::delete('/{id}', [InscripcionSalaGrupalController::class, 'destroy'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(InscripcionPermission::WITHDRAW, 'sanctum'))
    ->name('destroy');
