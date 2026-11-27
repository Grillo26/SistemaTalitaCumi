<?php

use App\Modules\AgendaTurnos\Http\Controllers\Asistencia\AsistenciaReporteController;
use App\Modules\AgendaTurnos\Http\Controllers\Asistencia\AsistenciaTurnoController;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Registro de asistencia por turno — módulo 07, submódulo 04
|--------------------------------------------------------------------------
|
| El prefijo /api/agenda-turnos/asistencia y el middleware api se agregan
| desde AsistenciaTurnosServiceProvider. El alcance por sucursal se valida
| en la Query/Action y un turno fuera de alcance responde 404.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/turnos/{id}', [AsistenciaTurnoController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AsistenciaTurnosPermission::VER, 'sanctum'))
        ->name('turnos.show');

    Route::post('/turnos/{id}/registrar', [AsistenciaTurnoController::class, 'registrar'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AsistenciaTurnosPermission::REGISTRAR, 'sanctum'))
        ->name('turnos.registrar');

    Route::post('/turnos/{id}/corregir', [AsistenciaTurnoController::class, 'corregir'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AsistenciaTurnosPermission::REGISTRAR, 'sanctum'))
        ->name('turnos.corregir');

    Route::get('/indicadores', [AsistenciaReporteController::class, 'indicadores'])
        ->middleware(PermissionMiddleware::using(AsistenciaTurnosPermission::VER_INDICADORES, 'sanctum'))
        ->name('indicadores');

    Route::get('/resumen-diario', [AsistenciaReporteController::class, 'resumenDiario'])
        ->middleware(PermissionMiddleware::using(AsistenciaTurnosPermission::RESUMEN_DIARIO, 'sanctum'))
        ->name('resumen-diario');

    Route::get('/exportar', [AsistenciaReporteController::class, 'exportar'])
        ->middleware(PermissionMiddleware::using(AsistenciaTurnosPermission::EXPORTAR, 'sanctum'))
        ->name('exportar');
});
