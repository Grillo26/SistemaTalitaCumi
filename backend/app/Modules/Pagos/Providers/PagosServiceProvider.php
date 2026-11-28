<?php

namespace App\Modules\Pagos\Providers;

use App\Modules\Pagos\Contracts\ComprobantePagoContract;
use App\Modules\Pagos\Contracts\EloquentPagosDelDia;
use App\Modules\Pagos\Contracts\PagosDelDiaContract;
use App\Modules\Pagos\Contracts\PayableEntityContract;
use App\Modules\Pagos\Contracts\StubPayableEntityContract;
use App\Modules\Pagos\Contracts\UnavailableComprobantePago;
use App\Modules\Pagos\Events\PagoRegistrado;
use App\Modules\Pagos\Listeners\RecordPagoAudit;
use App\Modules\Pagos\Support\PagosModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 08 — Pagos y caja (submódulo 08.01: registro de pagos y saldos).
 *
 * Sin entidades pagables conectadas, el stub responde 503 y el registro no crea pagos.
 */
final class PagosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PayableEntityContract::class, StubPayableEntityContract::class);
        $this->app->bind(ComprobantePagoContract::class, UnavailableComprobantePago::class);
        $this->app->bind(PagosDelDiaContract::class, EloquentPagosDelDia::class);
    }

    public function boot(): void
    {
        Event::listen(PagoRegistrado::class, RecordPagoAudit::class);

        Route::middleware('api')
            ->prefix('api'.PagosModule::MODULE_URL)
            ->name('api.pagos.')
            ->group(__DIR__.'/../routes.php');
    }
}
