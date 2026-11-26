<?php

use App\Modules\InscripcionesAtenciones\Http\Controllers\AtencionComprobanteController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\AtencionExportController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\AtencionPagoController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\DiagnosticoAtencionController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\EntrevistaAtencionController;
use App\Modules\InscripcionesAtenciones\Support\AtencionPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Entrevistas y diagnósticos como atenciones — módulo 06, submódulo 03
|--------------------------------------------------------------------------
|
| El prefijo /api/inscripciones-atenciones/entrevista-diagnostico y el
| middleware api se agregan desde InscripcionesAtencionesServiceProvider.
| El alcance por sucursal se valida en Queries y Actions.
|
*/

Route::get('/entrevistas', [EntrevistaAtencionController::class, 'index'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas.index');

Route::get('/entrevistas/monto-esperado', [EntrevistaAtencionController::class, 'previewMonto'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas.monto-esperado');

Route::get('/entrevistas/exportar', [AtencionExportController::class, 'entrevistas'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::EXPORT, 'sanctum'))
    ->name('entrevistas.exportar');

Route::post('/entrevistas', [EntrevistaAtencionController::class, 'store'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::CREATE, 'sanctum'))
    ->name('entrevistas.store');

Route::get('/entrevistas/{id}', [EntrevistaAtencionController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas.show');

Route::patch('/entrevistas/{id}', [EntrevistaAtencionController::class, 'update'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::EDIT, 'sanctum'))
    ->name('entrevistas.update');

Route::post('/entrevistas/{id}/retirar', [EntrevistaAtencionController::class, 'withdraw'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::WITHDRAW, 'sanctum'))
    ->name('entrevistas.withdraw');

Route::delete('/entrevistas/{id}', [EntrevistaAtencionController::class, 'destroy'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::WITHDRAW, 'sanctum'))
    ->name('entrevistas.destroy');

Route::get('/entrevistas/{id}/pago', [AtencionPagoController::class, 'showEntrevista'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas.pago.show');

Route::post('/entrevistas/{id}/pago', [AtencionPagoController::class, 'storeEntrevista'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas.pago.store');

Route::get('/entrevistas/{id}/comprobante', [AtencionComprobanteController::class, 'showEntrevista'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas.comprobante');

Route::get('/entrevistas/{id}/comprobante/pdf', [AtencionComprobanteController::class, 'pdfEntrevista'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas.comprobante.pdf');

Route::get('/entrevistas-previas', [DiagnosticoAtencionController::class, 'entrevistasPrevias'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('entrevistas-previas');

Route::get('/diagnosticos', [DiagnosticoAtencionController::class, 'index'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.index');

Route::get('/diagnosticos/monto-esperado', [DiagnosticoAtencionController::class, 'previewMonto'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.monto-esperado');

Route::get('/diagnosticos/requisitos', [DiagnosticoAtencionController::class, 'requisitos'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.requisitos');

Route::get('/diagnosticos/exportar', [AtencionExportController::class, 'diagnosticos'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::EXPORT, 'sanctum'))
    ->name('diagnosticos.exportar');

Route::post('/diagnosticos', [DiagnosticoAtencionController::class, 'store'])
    ->middleware(PermissionMiddleware::using(AtencionPermission::CREATE, 'sanctum'))
    ->name('diagnosticos.store');

Route::get('/diagnosticos/{id}', [DiagnosticoAtencionController::class, 'show'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.show');

Route::patch('/diagnosticos/{id}', [DiagnosticoAtencionController::class, 'update'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::EDIT, 'sanctum'))
    ->name('diagnosticos.update');

Route::post('/diagnosticos/{id}/retirar', [DiagnosticoAtencionController::class, 'withdraw'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::WITHDRAW, 'sanctum'))
    ->name('diagnosticos.withdraw');

Route::post('/diagnosticos/{id}/vincular-entrevista', [DiagnosticoAtencionController::class, 'vincular'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::EDIT, 'sanctum'))
    ->name('diagnosticos.vincular');

Route::delete('/diagnosticos/{id}', [DiagnosticoAtencionController::class, 'destroy'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::WITHDRAW, 'sanctum'))
    ->name('diagnosticos.destroy');

Route::get('/diagnosticos/{id}/pago', [AtencionPagoController::class, 'showDiagnostico'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.pago.show');

Route::post('/diagnosticos/{id}/pago', [AtencionPagoController::class, 'storeDiagnostico'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.pago.store');

Route::get('/diagnosticos/{id}/comprobante', [AtencionComprobanteController::class, 'showDiagnostico'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.comprobante');

Route::get('/diagnosticos/{id}/comprobante/pdf', [AtencionComprobanteController::class, 'pdfDiagnostico'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(AtencionPermission::VIEW, 'sanctum'))
    ->name('diagnosticos.comprobante.pdf');
