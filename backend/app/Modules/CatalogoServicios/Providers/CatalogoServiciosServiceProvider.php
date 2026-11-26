<?php

namespace App\Modules\CatalogoServicios\Providers;

use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosDirectoryContract;
use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosExporterContract;
use App\Modules\CatalogoServicios\Contracts\EloquentCatalogoServiciosDirectory;
use App\Modules\CatalogoServicios\Contracts\ServicioUsageContract;
use App\Modules\CatalogoServicios\Contracts\StubServicioUsageContract;
use App\Modules\CatalogoServicios\Contracts\UnavailableCatalogoServiciosExporter;
use App\Modules\CatalogoServicios\Events\AreaColorChanged;
use App\Modules\CatalogoServicios\Events\AreaCreated;
use App\Modules\CatalogoServicios\Events\AreaStatusChanged;
use App\Modules\CatalogoServicios\Events\AreaUpdated;
use App\Modules\CatalogoServicios\Events\ServicioCreated;
use App\Modules\CatalogoServicios\Events\ServicioStatusChanged;
use App\Modules\CatalogoServicios\Events\ServicioUpdated;
use App\Modules\CatalogoServicios\Events\TipoServicioCreated;
use App\Modules\CatalogoServicios\Events\TipoServicioStatusChanged;
use App\Modules\CatalogoServicios\Events\TipoServicioUpdated;
use App\Modules\CatalogoServicios\Listeners\InvalidateCatalogoServiciosCache;
use App\Modules\CatalogoServicios\Listeners\RecordCatalogoServiciosAudit;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 05 — Catálogo de servicios y tarifas.
 *
 * Submódulo 01: áreas, servicios y colores. Los submódulos 02 (tarifas) y
 * 03 (tipos de diagnóstico) reutilizan este provider y prefijo.
 */
final class CatalogoServiciosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CatalogoServiciosDirectoryContract::class,
            EloquentCatalogoServiciosDirectory::class,
        );
        $this->app->bind(ServicioUsageContract::class, StubServicioUsageContract::class);
        $this->app->bind(
            CatalogoServiciosExporterContract::class,
            UnavailableCatalogoServiciosExporter::class,
        );
    }

    public function boot(): void
    {
        foreach ([
            AreaCreated::class,
            AreaUpdated::class,
            AreaColorChanged::class,
            AreaStatusChanged::class,
            ServicioCreated::class,
            ServicioUpdated::class,
            ServicioStatusChanged::class,
            TipoServicioCreated::class,
            TipoServicioUpdated::class,
            TipoServicioStatusChanged::class,
        ] as $event) {
            Event::listen($event, RecordCatalogoServiciosAudit::class);
            Event::listen($event, InvalidateCatalogoServiciosCache::class);
        }

        Route::middleware('api')
            ->prefix('api'.CatalogoServiciosModule::MODULE_URL)
            ->name('api.catalogo-servicios.')
            ->group(function (): void {
                Route::prefix('areas')->name('areas.')->group(__DIR__.'/../routes-areas.php');
                Route::prefix('servicios')->name('servicios.')->group(__DIR__.'/../routes-servicios.php');
                Route::prefix('tipos-servicio')->name('tipos-servicio.')->group(__DIR__.'/../routes-tipos-servicio.php');
            });
    }
}
