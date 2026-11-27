<?php

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Http\Controllers\VigenciaConfiguracionController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\VigenciaPacienteController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\VigenciaPanelController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\VigenciaRenovacionController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\VigenciaReporteController;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPermission;
use App\Modules\InscripcionesAtenciones\Support\PaquetePermission;
use App\Modules\InscripcionesAtenciones\Support\VigenciaPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Vigencia, vencimiento y renovación — módulo 06, submódulo 04
|--------------------------------------------------------------------------
|
| El prefijo /api/inscripciones-atenciones y el middleware api se agregan
| desde InscripcionesAtencionesServiceProvider. El alcance por sucursal se
| valida en Queries y Actions.
|
*/

Route::get('/panel', [VigenciaPanelController::class, 'index'])
    ->middleware(PermissionMiddleware::using(VigenciaPermission::VIEW, 'sanctum'))
    ->name('panel');

Route::get('/reportes', [VigenciaReporteController::class, 'index'])
    ->middleware(PermissionMiddleware::using(VigenciaPermission::REPORTS, 'sanctum'))
    ->name('reportes');

Route::get('/reportes/exportar', [VigenciaReporteController::class, 'export'])
    ->middleware(PermissionMiddleware::using(VigenciaPermission::EXPORT, 'sanctum'))
    ->name('reportes.exportar');

Route::get('/pacientes/{pacienteId}/resumen', [VigenciaPacienteController::class, 'show'])
    ->whereNumber('pacienteId')
    ->middleware(PermissionMiddleware::using(VigenciaPermission::VIEW, 'sanctum'))
    ->name('pacientes.resumen');

Route::get('/entidades/{tipo}/{id}/renovacion', [VigenciaRenovacionController::class, 'preview'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(
        [InscripcionPermission::RENEW, PaquetePermission::RENEW],
        'sanctum',
    ))
    ->name('entidades.renovacion');

Route::post('/entidades/{tipo}/{id}/renovar', [VigenciaRenovacionController::class, 'renew'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(
        [InscripcionPermission::RENEW, PaquetePermission::RENEW],
        'sanctum',
    ))
    ->name('entidades.renovar');

Route::post('/renovaciones', [VigenciaRenovacionController::class, 'multiple'])
    ->middleware(PermissionMiddleware::using(
        [InscripcionPermission::RENEW, PaquetePermission::RENEW],
        'sanctum',
    ))
    ->name('renovaciones.multiple');

Route::get('/configuracion/ventana', [VigenciaConfiguracionController::class, 'show'])
    ->middleware(PermissionMiddleware::using(VigenciaPermission::CONFIG, 'sanctum'))
    ->name('configuracion.ventana.show');

Route::patch('/configuracion/ventana', [VigenciaConfiguracionController::class, 'update'])
    ->middleware(PermissionMiddleware::using(VigenciaPermission::CONFIG, 'sanctum'))
    ->name('configuracion.ventana.update');
