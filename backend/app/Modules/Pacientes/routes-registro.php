<?php

use App\Modules\Pacientes\Http\Controllers\Registro\PacienteController;
use App\Modules\Pacientes\Http\Controllers\Registro\PacienteStatusController;
use App\Modules\Pacientes\Http\Controllers\Tutores\PacienteTutorController;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del registro de pacientes (04.01)
|--------------------------------------------------------------------------
|
| El prefijo /api/pacientes y el middleware api se agregan desde
| PacientesServiceProvider. El alcance por sucursal se valida en Queries y
| Actions. La ficha (GET /{id}) es el único endpoint que audita consulta.
|
*/

Route::middleware('auth:sanctum')->prefix('pacientes')->group(function (): void {
    Route::get('/', [PacienteController::class, 'index'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/opciones', [PacienteController::class, 'options'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
        ->name('options');

    Route::post('/', [PacienteController::class, 'store'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_CREATE, 'sanctum'))
        ->name('store');

    Route::get('/{id}', [PacienteController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
        ->name('show');

    Route::patch('/{id}', [PacienteController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_EDIT, 'sanctum'))
        ->name('update');

    Route::post('/{id}/estado', [PacienteStatusController::class, 'changeStatus'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_CHANGE_STATUS, 'sanctum'))
        ->name('status');

    Route::delete('/{id}', [PacienteController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_CHANGE_STATUS, 'sanctum'))
        ->name('destroy');

    Route::get('/{id}/tutores', [PacienteTutorController::class, 'index'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_VIEW, 'sanctum'))
        ->name('tutores.index');

    Route::post('/{id}/tutores', [PacienteTutorController::class, 'store'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_EDIT, 'sanctum'))
        ->name('tutores.store');

    Route::post('/{id}/tutores/{tutorId}/principal', [PacienteTutorController::class, 'setPrincipal'])
        ->whereNumber('id')
        ->whereNumber('tutorId')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_EDIT, 'sanctum'))
        ->name('tutores.principal');

    Route::delete('/{id}/tutores/{tutorId}', [PacienteTutorController::class, 'destroy'])
        ->whereNumber('id')
        ->whereNumber('tutorId')
        ->middleware(PermissionMiddleware::using(PacientesPermission::PATIENT_EDIT, 'sanctum'))
        ->name('tutores.destroy');
});
