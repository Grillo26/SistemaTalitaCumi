<?php

use App\Modules\Profesionales\Http\Controllers\Matriz\MatrizController;
use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas de la matriz profesional–especialidad–sucursal (03 / Fase 2)
|--------------------------------------------------------------------------
|
| El prefijo /api/profesionales y el middleware api se agregan desde
| ProfesionalesServiceProvider. La matriz es de solo lectura y aplica el
| alcance por sucursal en la Query.
|
*/

Route::middleware('auth:sanctum')->prefix('matriz')->group(function (): void {
    Route::get('/', [MatrizController::class, 'index'])
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_VIEW_MATRIX, 'sanctum'))
        ->name('index');

    Route::get('/opciones', [MatrizController::class, 'options'])
        ->middleware(PermissionMiddleware::using(ProfesionalesPermission::PROFESSIONAL_VIEW_MATRIX, 'sanctum'))
        ->name('options');
});
