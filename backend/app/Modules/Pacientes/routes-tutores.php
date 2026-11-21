<?php

use App\Modules\Pacientes\Http\Controllers\Tutores\TutorController;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del registro de tutores (04.02 núcleo)
|--------------------------------------------------------------------------
|
| El prefijo /api/pacientes y el middleware api se agregan desde
| PacientesServiceProvider.
|
*/

Route::middleware('auth:sanctum')->prefix('tutores')->group(function (): void {
    Route::get('/', [TutorController::class, 'index'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_VIEW, 'sanctum'))
        ->name('index');

    Route::post('/', [TutorController::class, 'store'])
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_CREATE, 'sanctum'))
        ->name('store');

    Route::get('/{id}', [TutorController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_VIEW, 'sanctum'))
        ->name('show');

    Route::patch('/{id}', [TutorController::class, 'update'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(PacientesPermission::TUTOR_EDIT, 'sanctum'))
        ->name('update');
});
