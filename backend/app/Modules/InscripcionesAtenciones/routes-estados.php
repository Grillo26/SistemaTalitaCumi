<?php

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Http\Controllers\EstadoInscripcionController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\EstadoOpcionesController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\EstadoPacienteController;
use App\Modules\InscripcionesAtenciones\Http\Controllers\EstadoReporteController;
use App\Modules\InscripcionesAtenciones\Support\EstadoPermission;
use App\Modules\InscripcionesAtenciones\Support\InscripcionPermission;
use App\Modules\InscripcionesAtenciones\Support\PaquetePermission;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Estados del paciente y de la inscripción — módulo 06, submódulo 05
|--------------------------------------------------------------------------
|
| Prefijo /api/inscripciones-atenciones/estados. El alcance por sucursal y
| el tipo de entidad se validan en Queries y Actions.
|
*/

Route::get('/opciones', EstadoOpcionesController::class)
    ->middleware(PermissionMiddleware::using(EstadoPermission::VIEW, 'sanctum'))
    ->name('opciones');

Route::get('/pacientes', [EstadoPacienteController::class, 'index'])
    ->middleware(PermissionMiddleware::using(EstadoPermission::VIEW, 'sanctum'))
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
    ->name('pacientes.index');

Route::get('/pacientes/{id}/historial', [EstadoPacienteController::class, 'historial'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(EstadoPermission::VIEW, 'sanctum'))
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
    ->name('pacientes.historial');

Route::post('/pacientes/{id}/pausar', [EstadoPacienteController::class, 'pausar'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_CHANGE_STATUS, 'sanctum'))
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
    ->name('pacientes.pausar');

Route::post('/pacientes/{id}/reactivar', [EstadoPacienteController::class, 'reactivar'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_CHANGE_STATUS, 'sanctum'))
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
    ->name('pacientes.reactivar');

Route::post('/pacientes/{id}/retirar', [EstadoPacienteController::class, 'retirar'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_RETIRE, 'sanctum'))
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
    ->name('pacientes.retirar');

Route::get('/pacientes/{id}/retiro/preview', [EstadoPacienteController::class, 'previewRetiro'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_RETIRE, 'sanctum'))
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
    ->name('pacientes.retiro.preview');

Route::get('/pacientes/{id}/retiro/prechequeo', [EstadoPacienteController::class, 'prechequeoRetiro'])
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_RETIRE, 'sanctum'))
    ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
    ->name('pacientes.retiro.prechequeo');

Route::get('/inscripciones', [EstadoInscripcionController::class, 'index'])
    ->middleware(PermissionMiddleware::using(EstadoPermission::VIEW, 'sanctum'))
    ->name('inscripciones.index');

Route::get('/inscripciones/{tipo}/{id}/historial', [EstadoInscripcionController::class, 'historial'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(EstadoPermission::VIEW, 'sanctum'))
    ->name('inscripciones.historial');

Route::post('/inscripciones/{tipo}/{id}/pausar', [EstadoInscripcionController::class, 'pausar'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(EstadoPermission::INSCRIPTION_PAUSE, 'sanctum'))
    ->name('inscripciones.pausar');

Route::post('/inscripciones/{tipo}/{id}/reactivar', [EstadoInscripcionController::class, 'reactivar'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(EstadoPermission::INSCRIPTION_REACTIVATE, 'sanctum'))
    ->name('inscripciones.reactivar');

Route::post('/inscripciones/{tipo}/{id}/retirar', [EstadoInscripcionController::class, 'retirar'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using([
        InscripcionPermission::WITHDRAW,
        PaquetePermission::WITHDRAW,
    ], 'sanctum'))
    ->name('inscripciones.retirar');

Route::get('/inscripciones/{tipo}/{id}/prechequeo-pausa', [EstadoInscripcionController::class, 'prechequeoPausa'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(EstadoPermission::INSCRIPTION_PAUSE, 'sanctum'))
    ->name('inscripciones.prechequeo-pausa');

Route::get('/inscripciones/{tipo}/{id}/prechequeo-retiro', [EstadoInscripcionController::class, 'prechequeoRetiro'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using([
        InscripcionPermission::WITHDRAW,
        PaquetePermission::WITHDRAW,
    ], 'sanctum'))
    ->name('inscripciones.prechequeo-retiro');

Route::get('/inscripciones/{tipo}/{id}/prechequeo-reactivacion', [EstadoInscripcionController::class, 'prechequeoReactivacion'])
    ->whereIn('tipo', TipoEntidadVigencia::values())
    ->whereNumber('id')
    ->middleware(PermissionMiddleware::using(EstadoPermission::INSCRIPTION_REACTIVATE, 'sanctum'))
    ->name('inscripciones.prechequeo-reactivacion');

Route::get('/reportes', [EstadoReporteController::class, 'index'])
    ->middleware(PermissionMiddleware::using(EstadoPermission::REPORTS, 'sanctum'))
    ->name('reportes');

Route::get('/reportes/exportar', [EstadoReporteController::class, 'export'])
    ->middleware(PermissionMiddleware::using(EstadoPermission::EXPORT, 'sanctum'))
    ->name('reportes.exportar');
