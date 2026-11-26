<?php

namespace App\Modules\InscripcionesAtenciones\Providers;

use App\Modules\InscripcionesAtenciones\Contracts\AsistenciaInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobantePdfExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\InscripcionExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubAsistenciaInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubPagoInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubTurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableComprobantePdfExporter;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableInscripcionExporter;
use App\Modules\InscripcionesAtenciones\Events\InscripcionCreated;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRenewed;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRetirada;
use App\Modules\InscripcionesAtenciones\Events\InscripcionUpdated;
use App\Modules\InscripcionesAtenciones\Listeners\RecordInscripcionAudit;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 06 — Inscripciones y atenciones.
 *
 * Submódulo 01: inscripción a salas grupales. Los futuros submódulos
 * reutilizan el prefijo, el provider y el catálogo de auditoría.
 */
final class InscripcionesAtencionesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PagoInscripcionContract::class, StubPagoInscripcionContract::class);
        $this->app->bind(AsistenciaInscripcionContract::class, StubAsistenciaInscripcionContract::class);
        $this->app->bind(TurnosFuturosInscripcionContract::class, StubTurnosFuturosInscripcionContract::class);
        $this->app->bind(ComprobantePdfExporterContract::class, UnavailableComprobantePdfExporter::class);
        $this->app->bind(InscripcionExporterContract::class, UnavailableInscripcionExporter::class);
    }

    public function boot(): void
    {
        foreach ([
            InscripcionCreated::class,
            InscripcionUpdated::class,
            InscripcionRenewed::class,
            InscripcionRetirada::class,
        ] as $event) {
            Event::listen($event, RecordInscripcionAudit::class);
        }

        Route::middleware('api')
            ->prefix('api'.InscripcionesAtencionesModule::MODULE_URL)
            ->name('api.inscripciones-atenciones.')
            ->group(__DIR__.'/../routes.php');
    }
}
