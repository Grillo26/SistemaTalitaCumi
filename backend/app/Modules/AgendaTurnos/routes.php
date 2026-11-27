<?php

use App\Modules\AgendaTurnos\Http\Controllers\AgendaExportController;
use App\Modules\AgendaTurnos\Http\Controllers\AgendaSemanalController;
use App\Modules\AgendaTurnos\Http\Controllers\TurnoDetalleController;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Agenda semanal por sucursal — módulo 07, submódulo 01
|--------------------------------------------------------------------------
|
| El prefijo /api/agenda-turnos/agenda-semanal y el middleware api se
| agregan desde AgendaTurnosServiceProvider. El alcance por sucursal se
| valida de forma explícita en las Queries.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [AgendaSemanalController::class, 'index'])
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::VER, 'sanctum'))
        ->name('index');

    Route::get('/opciones', [AgendaSemanalController::class, 'opciones'])
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::VER, 'sanctum'))
        ->name('opciones');

    Route::get('/sucursales', [AgendaSemanalController::class, 'sucursales'])
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::VER, 'sanctum'))
        ->name('sucursales');

    Route::get('/exportar', [AgendaExportController::class, 'export'])
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::EXPORTAR, 'sanctum'))
        ->name('exportar');

    Route::get('/turnos/{id}', [TurnoDetalleController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(AgendaTurnosPermission::VER, 'sanctum'))
        ->name('turnos.show');
});
