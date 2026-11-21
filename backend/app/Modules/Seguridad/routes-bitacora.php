<?php

use App\Modules\Seguridad\Http\Controllers\AuditController;
use App\Modules\Seguridad\Support\SecurityPermission;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;

/*
|--------------------------------------------------------------------------
| Rutas del pilar de auditoría (02 / Fase 4)
|--------------------------------------------------------------------------
|
| Prefijo /api/seguridad/bitacora. Solo lectura; la exportación queda
| pendiente de infraestructura y no se expone.
|
*/

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/', [AuditController::class, 'index'])
        ->middleware(PermissionMiddleware::using(SecurityPermission::AUDIT_VIEW, 'sanctum'))
        ->name('index');

    Route::get('/opciones', [AuditController::class, 'options'])
        ->middleware(PermissionMiddleware::using(SecurityPermission::AUDIT_VIEW, 'sanctum'))
        ->name('options');

    Route::get('/reconstruir', [AuditController::class, 'reconstruct'])
        ->middleware(PermissionMiddleware::using(SecurityPermission::AUDIT_VIEW, 'sanctum'))
        ->name('reconstruct');

    Route::get('/{id}', [AuditController::class, 'show'])
        ->whereNumber('id')
        ->middleware(PermissionMiddleware::using(SecurityPermission::AUDIT_VIEW, 'sanctum'))
        ->name('show');
});
