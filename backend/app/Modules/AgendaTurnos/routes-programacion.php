<?php

use App\Modules\AgendaTurnos\Http\Controllers\FeriadoController;
use App\Modules\AgendaTurnos\Http\Controllers\ProgramacionTurnosController;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Programación de turnos y patrones de días — módulo 07, submódulo 02
|--------------------------------------------------------------------------
|
| El prefijo /api/agenda-turnos/programacion-turnos y el middleware api se
| agregan desde AgendaTurnosServiceProvider. No existe DELETE de turnos: la
| cancelación por regeneración es interna y la individual pertenece a 7.3.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/origenes/{tipo}/{id}', [ProgramacionTurnosController::class, 'origen'])
        ->where('tipo', 'inscripcion_sala|paquete_sesiones')
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::VER, 'sanctum'))
        ->name('origenes.show');

    Route::get('/origenes/{tipo}/{id}/turnos', [ProgramacionTurnosController::class, 'turnos'])
        ->where('tipo', 'inscripcion_sala|paquete_sesiones')
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::VER, 'sanctum'))
        ->name('origenes.turnos');

    Route::get(
        '/origenes/{tipo}/{id}/preview-generacion',
        [ProgramacionTurnosController::class, 'previewGeneracion'],
    )
        ->where('tipo', 'inscripcion_sala|paquete_sesiones')
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::GENERAR, 'sanctum'))
        ->name('origenes.preview-generacion');

    Route::post('/origenes/{tipo}/{id}/generar', [ProgramacionTurnosController::class, 'generar'])
        ->where('tipo', 'inscripcion_sala|paquete_sesiones')
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::GENERAR, 'sanctum'))
        ->name('origenes.generar');

    Route::get(
        '/origenes/{tipo}/{id}/preview-regeneracion',
        [ProgramacionTurnosController::class, 'previewRegeneracion'],
    )
        ->where('tipo', 'inscripcion_sala|paquete_sesiones')
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::REGENERAR, 'sanctum'))
        ->name('origenes.preview-regeneracion');

    Route::post('/origenes/{tipo}/{id}/regenerar', [ProgramacionTurnosController::class, 'regenerar'])
        ->where('tipo', 'inscripcion_sala|paquete_sesiones')
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::REGENERAR, 'sanctum'))
        ->name('origenes.regenerar');

    Route::post('/turnos/asignar-profesional', [ProgramacionTurnosController::class, 'asignarProfesional'])
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::ASIGNAR_PROFESIONAL, 'sanctum'))
        ->name('turnos.asignar-profesional');

    Route::get('/feriados', [FeriadoController::class, 'index'])
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::FERIADOS, 'sanctum'))
        ->name('feriados.index');

    Route::post('/feriados', [FeriadoController::class, 'store'])
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::FERIADOS, 'sanctum'))
        ->name('feriados.store');

    Route::patch('/feriados/{id}', [FeriadoController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::FERIADOS, 'sanctum'))
        ->name('feriados.update');

    Route::post('/feriados/{id}/desactivar', [FeriadoController::class, 'desactivar'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::FERIADOS, 'sanctum'))
        ->name('feriados.desactivar');
});
