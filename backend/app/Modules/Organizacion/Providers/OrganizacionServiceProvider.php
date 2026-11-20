<?php

namespace App\Modules\Organizacion\Providers;

use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\EloquentBranchDirectory;
use App\Modules\Organizacion\Contracts\StubUserBranchAccessContract;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Events\BranchActivated;
use App\Modules\Organizacion\Events\BranchCreated;
use App\Modules\Organizacion\Events\BranchDeactivated;
use App\Modules\Organizacion\Events\BranchUpdated;
use App\Modules\Organizacion\Listeners\RecordBranchAudit;
use App\Modules\Organizacion\Support\OrganizacionModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

final class OrganizacionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(UserBranchAccessContract::class, StubUserBranchAccessContract::class);
        $this->app->bind(BranchDirectoryContract::class, EloquentBranchDirectory::class);
    }

    public function boot(): void
    {
        Event::listen(BranchCreated::class, RecordBranchAudit::class);
        Event::listen(BranchUpdated::class, RecordBranchAudit::class);
        Event::listen(BranchActivated::class, RecordBranchAudit::class);
        Event::listen(BranchDeactivated::class, RecordBranchAudit::class);

        Route::middleware('api')
            ->prefix('api'.OrganizacionModule::MODULE_URL)
            ->name('api.organizacion.sucursales.')
            ->group(__DIR__.'/../routes.php');
    }
}
