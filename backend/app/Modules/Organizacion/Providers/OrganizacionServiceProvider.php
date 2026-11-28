<?php

namespace App\Modules\Organizacion\Providers;

use App\Modules\Organizacion\Contracts\AmbienteDirectoryContract;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\EloquentAmbienteDirectory;
use App\Modules\Organizacion\Contracts\EloquentBranchDirectory;
use App\Modules\Organizacion\Contracts\EloquentParameterAdmin;
use App\Modules\Organizacion\Contracts\EloquentParameterReader;
use App\Modules\Organizacion\Contracts\EloquentSalaDirectory;
use App\Modules\Organizacion\Contracts\ParameterAdminContract;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use App\Modules\Organizacion\Contracts\SalaPatronContract;
use App\Modules\Organizacion\Contracts\StubSalaPatronContract;
use App\Modules\Organizacion\Events\AmbienteCreated;
use App\Modules\Organizacion\Events\AmbienteStatusChanged;
use App\Modules\Organizacion\Events\AmbienteUpdated;
use App\Modules\Organizacion\Events\BranchActivated;
use App\Modules\Organizacion\Events\BranchCreated;
use App\Modules\Organizacion\Events\BranchDeactivated;
use App\Modules\Organizacion\Events\BranchUpdated;
use App\Modules\Organizacion\Events\ParameterCreated;
use App\Modules\Organizacion\Events\ParameterRestored;
use App\Modules\Organizacion\Events\ParameterUpdated;
use App\Modules\Organizacion\Events\SalaCreated;
use App\Modules\Organizacion\Events\SalaStatusChanged;
use App\Modules\Organizacion\Events\SalaUpdated;
use App\Modules\Organizacion\Events\TipoSalaCreated;
use App\Modules\Organizacion\Events\TipoSalaStatusChanged;
use App\Modules\Organizacion\Events\TipoSalaUpdated;
use App\Modules\Organizacion\Events\TurnoCatalogoCreated;
use App\Modules\Organizacion\Events\TurnoCatalogoStatusChanged;
use App\Modules\Organizacion\Events\TurnoCatalogoUpdated;
use App\Modules\Organizacion\Listeners\InvalidateParameterCache;
use App\Modules\Organizacion\Listeners\RecordBranchAudit;
use App\Modules\Organizacion\Listeners\RecordParameterAudit;
use App\Modules\Organizacion\Listeners\RecordSalasAmbientesAudit;
use App\Modules\Organizacion\Support\ConfiguracionModule;
use App\Modules\Organizacion\Support\OrganizacionModule;
use App\Modules\Organizacion\Support\SalasAmbientesModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class OrganizacionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(BranchDirectoryContract::class, EloquentBranchDirectory::class);
        $this->app->bind(SalaDirectoryContract::class, EloquentSalaDirectory::class);
        $this->app->bind(AmbienteDirectoryContract::class, EloquentAmbienteDirectory::class);
        $this->app->bind(ParameterReaderContract::class, EloquentParameterReader::class);
        $this->app->bind(ParameterAdminContract::class, EloquentParameterAdmin::class);
        $this->app->bind(SalaPatronContract::class, StubSalaPatronContract::class);
    }

    public function boot(): void
    {
        Event::listen(BranchCreated::class, RecordBranchAudit::class);
        Event::listen(BranchUpdated::class, RecordBranchAudit::class);
        Event::listen(BranchActivated::class, RecordBranchAudit::class);
        Event::listen(BranchDeactivated::class, RecordBranchAudit::class);

        Event::listen(TipoSalaCreated::class, RecordSalasAmbientesAudit::class);
        Event::listen(TipoSalaUpdated::class, RecordSalasAmbientesAudit::class);
        Event::listen(TipoSalaStatusChanged::class, RecordSalasAmbientesAudit::class);
        Event::listen(TurnoCatalogoCreated::class, RecordSalasAmbientesAudit::class);
        Event::listen(TurnoCatalogoUpdated::class, RecordSalasAmbientesAudit::class);
        Event::listen(TurnoCatalogoStatusChanged::class, RecordSalasAmbientesAudit::class);
        Event::listen(SalaCreated::class, RecordSalasAmbientesAudit::class);
        Event::listen(SalaUpdated::class, RecordSalasAmbientesAudit::class);
        Event::listen(SalaStatusChanged::class, RecordSalasAmbientesAudit::class);
        Event::listen(AmbienteCreated::class, RecordSalasAmbientesAudit::class);
        Event::listen(AmbienteUpdated::class, RecordSalasAmbientesAudit::class);
        Event::listen(AmbienteStatusChanged::class, RecordSalasAmbientesAudit::class);

        foreach ([ParameterCreated::class, ParameterUpdated::class, ParameterRestored::class] as $event) {
            Event::listen($event, RecordParameterAudit::class);
            Event::listen($event, InvalidateParameterCache::class);
        }

        Route::middleware('api')
            ->prefix('api'.OrganizacionModule::MODULE_URL)
            ->name('api.organizacion.sucursales.')
            ->group(__DIR__.'/../routes.php');

        Route::middleware('api')
            ->prefix('api'.SalasAmbientesModule::MODULE_URL)
            ->name('api.organizacion.salas-ambientes.')
            ->group(__DIR__.'/../routes-salas-ambientes.php');

        Route::middleware('api')
            ->prefix('api'.ConfiguracionModule::MODULE_URL)
            ->name('api.organizacion.configuracion.')
            ->group(__DIR__.'/../routes-configuracion.php');
    }
}
