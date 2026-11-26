<?php

namespace App\Modules\CatalogoServicios\Providers;

use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosDirectoryContract;
use App\Modules\CatalogoServicios\Contracts\CatalogoServiciosExporterContract;
use App\Modules\CatalogoServicios\Contracts\EloquentCatalogoServiciosDirectory;
use App\Modules\CatalogoServicios\Contracts\EloquentServicioUsageContract;
use App\Modules\CatalogoServicios\Contracts\EloquentTarifaAplicableResolver;
use App\Modules\CatalogoServicios\Contracts\ServicioUsageContract;
use App\Modules\CatalogoServicios\Contracts\StubTarifaUsageContract;
use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResolverContract;
use App\Modules\CatalogoServicios\Contracts\TarifaUsageContract;
use App\Modules\CatalogoServicios\Contracts\UnavailableCatalogoServiciosExporter;
use App\Modules\CatalogoServicios\Events\AreaColorChanged;
use App\Modules\CatalogoServicios\Events\AreaCreated;
use App\Modules\CatalogoServicios\Events\AreaStatusChanged;
use App\Modules\CatalogoServicios\Events\AreaUpdated;
use App\Modules\CatalogoServicios\Events\ServicioCreated;
use App\Modules\CatalogoServicios\Events\ServicioStatusChanged;
use App\Modules\CatalogoServicios\Events\ServicioUpdated;
use App\Modules\CatalogoServicios\Events\CategoriaTarifaCreated;
use App\Modules\CatalogoServicios\Events\CategoriaTarifaStatusChanged;
use App\Modules\CatalogoServicios\Events\CategoriaTarifaUpdated;
use App\Modules\CatalogoServicios\Events\TarifaCreated;
use App\Modules\CatalogoServicios\Events\TarifaUpdated;
use App\Modules\CatalogoServicios\Events\TarifaVigenciaClosed;
use App\Modules\CatalogoServicios\Events\TipoServicioCreated;
use App\Modules\CatalogoServicios\Events\TipoServicioStatusChanged;
use App\Modules\CatalogoServicios\Events\TipoServicioUpdated;
use App\Modules\CatalogoServicios\Listeners\InvalidateCatalogoServiciosCache;
use App\Modules\CatalogoServicios\Listeners\RecordCatalogoServiciosAudit;
use App\Modules\CatalogoServicios\Listeners\RecordCatalogoTarifasAudit;
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
        $this->app->bind(ServicioUsageContract::class, EloquentServicioUsageContract::class);
        $this->app->bind(TarifaAplicableResolverContract::class, EloquentTarifaAplicableResolver::class);
        $this->app->bind(TarifaUsageContract::class, StubTarifaUsageContract::class);
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

        foreach ([
            CategoriaTarifaCreated::class,
            CategoriaTarifaUpdated::class,
            CategoriaTarifaStatusChanged::class,
            TarifaCreated::class,
            TarifaUpdated::class,
            TarifaVigenciaClosed::class,
        ] as $event) {
            Event::listen($event, RecordCatalogoTarifasAudit::class);
            Event::listen($event, InvalidateCatalogoServiciosCache::class);
        }

        Route::middleware('api')
            ->prefix('api'.CatalogoServiciosModule::MODULE_URL)
            ->name('api.catalogo-servicios.')
            ->group(function (): void {
                Route::prefix('areas')->name('areas.')->group(__DIR__.'/../routes-areas.php');
                Route::prefix('servicios')->name('servicios.')->group(__DIR__.'/../routes-servicios.php');
                Route::prefix('tipos-servicio')->name('tipos-servicio.')->group(__DIR__.'/../routes-tipos-servicio.php');
                Route::prefix('categorias-tarifa')->name('categorias-tarifa.')->group(__DIR__.'/../routes-categorias-tarifa.php');
                Route::prefix('tarifas')->name('tarifas.')->group(__DIR__.'/../routes-tarifas.php');
            });
    }
}
