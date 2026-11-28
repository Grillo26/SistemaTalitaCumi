<?php

use App\Modules\AgendaTurnos\Http\Controllers\Cambios\CambiosTurnoController;
use App\Modules\AgendaTurnos\Http\Controllers\Cambios\ReporteCambiosController;
use App\Modules\AgendaTurnos\Support\CambiosTurnosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Reprogramación y cancelación de turnos — módulo 07, submódulo 03
|--------------------------------------------------------------------------
|
| El prefijo /api/agenda-turnos/cambios-turnos y el middleware api se agregan
| desde CambiosTurnosServiceProvider. No existe DELETE de turnos.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/turnos/{id}', [CambiosTurnoController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using('turno.ver', 'sanctum'))
        ->name('turnos.show');

    Route::get('/turnos/{id}/historial', [CambiosTurnoController::class, 'historial'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::HISTORIAL, 'sanctum'))
        ->name('turnos.historial');

    Route::get('/turnos/{id}/contacto-whatsapp', [CambiosTurnoController::class, 'whatsapp'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using('turno.ver', 'sanctum'))
        ->name('turnos.whatsapp');

    Route::post(
        '/turnos/{id}/preview-reprogramacion',
        [CambiosTurnoController::class, 'previewReprogramacion'],
    )
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::REPROGRAMAR, 'sanctum'))
        ->name('turnos.preview-reprogramacion');

    Route::post('/turnos/{id}/reprogramar', [CambiosTurnoController::class, 'reprogramar'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::REPROGRAMAR, 'sanctum'))
        ->name('turnos.reprogramar');

    Route::post(
        '/turnos/{id}/preview-cancelacion',
        [CambiosTurnoController::class, 'previewCancelacion'],
    )
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::CANCELAR, 'sanctum'))
        ->name('turnos.preview-cancelacion');

    Route::post('/turnos/{id}/cancelar', [CambiosTurnoController::class, 'cancelar'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::CANCELAR, 'sanctum'))
        ->name('turnos.cancelar');

    Route::get('/motivos', [CambiosTurnoController::class, 'motivos'])
        ->middleware(PermissionMiddleware::using('turno.ver', 'sanctum'))
        ->name('motivos.index');

    Route::get(
        '/pacientes/{pacienteId}/origenes-cancelables',
        [CambiosTurnoController::class, 'origenesCancelables'],
    )
        ->whereNumber('pacienteId')
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::CANCELAR_MASIVO, 'sanctum'))
        ->name('pacientes.origenes-cancelables');

    Route::post('/cancelacion-masiva/preview', [CambiosTurnoController::class, 'previewMasiva'])
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::CANCELAR_MASIVO, 'sanctum'))
        ->name('cancelacion-masiva.preview');

    Route::post('/cancelacion-masiva', [CambiosTurnoController::class, 'cancelarMasivo'])
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::CANCELAR_MASIVO, 'sanctum'))
        ->name('cancelacion-masiva.ejecutar');

    Route::get('/reporte-cambios', [ReporteCambiosController::class, 'index'])
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::REPORTE, 'sanctum'))
        ->name('reporte-cambios.index');

    Route::get('/reporte-cambios/exportar', [ReporteCambiosController::class, 'export'])
        ->middleware(PermissionMiddleware::using(CambiosTurnosPermission::EXPORTAR, 'sanctum'))
        ->name('reporte-cambios.exportar');
});
