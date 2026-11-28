<?php

namespace App\Modules\AgendaTurnos;

use App\Modules\AgendaTurnos\Http\Controllers\Diagnosticos\DiagnosticoCoordinacionController;
use App\Modules\AgendaTurnos\Support\DiagnosticosPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Diagnóstico con varios profesionales — módulo 07, submódulo 05
|--------------------------------------------------------------------------
|
| El prefijo /api/agenda-turnos/diagnosticos y el middleware api se agregan
| desde DiagnosticosServiceProvider. Las rutas estáticas van antes que {id}.
| No existe DELETE: cancelar el diagnóstico es un cambio de estado.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/opciones', [DiagnosticoCoordinacionController::class, 'opciones'])
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::VER, 'sanctum'))
        ->name('opciones');

    Route::get('/exportar', [DiagnosticoCoordinacionController::class, 'exportar'])
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::EXPORTAR, 'sanctum'))
        ->name('exportar');

    Route::get('/', [DiagnosticoCoordinacionController::class, 'index'])
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::VER, 'sanctum'))
        ->name('index');

    Route::get('/{id}', [DiagnosticoCoordinacionController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::VER, 'sanctum'))
        ->name('show');

    Route::post('/{id}/turnos', [DiagnosticoCoordinacionController::class, 'programar'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::PROGRAMAR, 'sanctum'))
        ->name('turnos.store');

    Route::patch('/{id}/estado-general', [DiagnosticoCoordinacionController::class, 'actualizarEstado'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::ACTUALIZAR_ESTADO, 'sanctum'))
        ->name('estado-general.update');

    Route::post('/{id}/cancelar', [DiagnosticoCoordinacionController::class, 'cancelar'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::CANCELAR, 'sanctum'))
        ->name('cancelar');

    Route::get('/{id}/whatsapp', [DiagnosticoCoordinacionController::class, 'whatsapp'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(DiagnosticosPermission::WHATSAPP, 'sanctum'))
        ->name('whatsapp');
});
