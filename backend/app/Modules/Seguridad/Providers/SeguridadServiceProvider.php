<?php

namespace App\Modules\Seguridad\Providers;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Seguridad\Console\Commands\CreateSuperadminCommand;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use App\Modules\Seguridad\Contracts\EloquentAuditRecorder;
use App\Modules\Seguridad\Contracts\EloquentPermissionEvaluator;
use App\Modules\Seguridad\Contracts\EloquentUserBranchAccess;
use App\Modules\Seguridad\Contracts\EloquentUserDirectory;
use App\Modules\Seguridad\Contracts\PermissionEvaluatorContract;
use App\Modules\Seguridad\Contracts\UserDirectoryContract;
use App\Modules\Seguridad\Events\RolePermissionsUpdated;
use App\Modules\Seguridad\Events\UserCreated;
use App\Modules\Seguridad\Events\UserLocked;
use App\Modules\Seguridad\Events\UserLoggedIn;
use App\Modules\Seguridad\Events\UserLoggedOut;
use App\Modules\Seguridad\Events\UserLoginFailed;
use App\Modules\Seguridad\Events\UserPasswordChanged;
use App\Modules\Seguridad\Events\UserPasswordReset;
use App\Modules\Seguridad\Events\UserSessionExpired;
use App\Modules\Seguridad\Events\UserSessionsClosed;
use App\Modules\Seguridad\Events\UserStatusChanged;
use App\Modules\Seguridad\Events\UserUnlocked;
use App\Modules\Seguridad\Events\UserUpdated;
use App\Modules\Seguridad\Listeners\RecordSecurityAudit;
use App\Modules\Seguridad\Support\AutenticacionModule;
use App\Modules\Seguridad\Support\BitacoraModule;
use App\Modules\Seguridad\Support\RolesModule;
use App\Modules\Seguridad\Support\UsuariosModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor del módulo 02 — Usuarios, roles y seguridad.
 *
 * Fase 1: autenticación y bitácora de accesos.
 * Fase 2: usuarios, alcance real por sucursal y directorio.
 * Fase 3: matriz de permisos y evaluador.
 * Fase 4: consulta de bitácora, reconstrucción y auditoría de terceros.
 */
final class SeguridadServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserBranchAccessContract::class, EloquentUserBranchAccess::class);
        $this->app->bind(UserDirectoryContract::class, EloquentUserDirectory::class);
        $this->app->bind(PermissionEvaluatorContract::class, EloquentPermissionEvaluator::class);
        $this->app->bind(AuditRecorderContract::class, EloquentAuditRecorder::class);
    }

    public function boot(): void
    {
        foreach ([
            UserLoggedIn::class,
            UserLoginFailed::class,
            UserLoggedOut::class,
            UserPasswordChanged::class,
            UserLocked::class,
            UserCreated::class,
            UserUpdated::class,
            UserStatusChanged::class,
            UserPasswordReset::class,
            UserUnlocked::class,
            UserSessionsClosed::class,
            UserSessionExpired::class,
            RolePermissionsUpdated::class,
        ] as $event) {
            Event::listen($event, RecordSecurityAudit::class);
        }

        Route::middleware('api')
            ->prefix('api'.AutenticacionModule::MODULE_URL)
            ->name('api.auth.')
            ->group(__DIR__.'/../routes-auth.php');

        Route::middleware('api')
            ->prefix('api'.UsuariosModule::MODULE_URL)
            ->name('api.seguridad.usuarios.')
            ->group(__DIR__.'/../routes-usuarios.php');

        Route::middleware('api')
            ->prefix('api'.RolesModule::MODULE_URL)
            ->name('api.seguridad.roles.')
            ->group(__DIR__.'/../routes-roles.php');

        Route::middleware('api')
            ->prefix('api'.BitacoraModule::MODULE_URL)
            ->name('api.seguridad.bitacora.')
            ->group(__DIR__.'/../routes-bitacora.php');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateSuperadminCommand::class,
            ]);
        }
    }
}
