<?php

use App\Modules\Pacientes\Http\Controllers\Resumen\PacienteResumenController;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas de la ficha resumen (04.04 Fase B)
|--------------------------------------------------------------------------
|
| La apertura del resumen se audita. El refresco de indicadores no se audita.
|
*/

Route::middleware('auth:sanctum')->prefix('pacientes')->group(function (): void {
    Route::get('/{id}/resumen/indicadores', [PacienteResumenController::class, 'indicators'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
        ->name('indicadores');

    Route::get('/{id}/resumen', [PacienteResumenController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
        ->name('show');
});
