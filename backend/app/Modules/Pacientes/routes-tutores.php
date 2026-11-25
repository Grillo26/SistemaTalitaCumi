<?php

use App\Modules\Pacientes\Http\Controllers\Tutores\TutorController;
use App\Modules\Pacientes\Http\Controllers\Tutores\TutorStatusController;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del registro de tutores (04.02)
|--------------------------------------------------------------------------
|
| El prefijo /api/pacientes y el middleware api se agregan desde
| PacientesServiceProvider. La ficha (GET /{id}) es el único endpoint que
| audita consulta.
|
*/

Route::middleware('auth:sanctum')->prefix('tutores')->group(function (): void {
    Route::get('/', [TutorController::class, 'index'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/opciones', [TutorController::class, 'options'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_VIEW, 'sanctum'))
        ->name('options');

    Route::get('/exportar', [TutorController::class, 'export'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_EXPORT, 'sanctum'))
        ->name('export');

    Route::post('/', [TutorController::class, 'store'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_CREATE, 'sanctum'))
        ->name('store');

    Route::get('/{id}', [TutorController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_VIEW, 'sanctum'))
        ->name('show');

    Route::patch('/{id}', [TutorController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(
            [PacientesPermission::TUTOR_EDIT, PacientesPermission::TUTOR_EDIT_CONTACT],
            'sanctum',
        ))
        ->name('update');

    Route::post('/{id}/estado', [TutorStatusController::class, 'changeStatus'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_CHANGE_STATUS, 'sanctum'))
        ->name('status');

    Route::delete('/{id}', [TutorStatusController::class, 'destroy'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_EDIT, 'sanctum'))
        ->name('destroy');
});
