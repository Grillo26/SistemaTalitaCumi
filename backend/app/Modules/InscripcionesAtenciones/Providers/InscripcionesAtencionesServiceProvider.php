<?php

namespace App\Modules\InscripcionesAtenciones\Providers;

use App\Modules\InscripcionesAtenciones\Contracts\AsistenciaInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\AtencionExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobanteAtencionPdfExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobantePaquetePdfExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobantePdfExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteBatchContract;
use App\Modules\InscripcionesAtenciones\Contracts\ConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\EloquentVigenciaEntidad;
use App\Modules\InscripcionesAtenciones\Contracts\InscripcionExporterContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\PagoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubAsistenciaInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubConsumoPaqueteBatchContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubConsumoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubPagoAtencionContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubPagoInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubPagoPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubTurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\StubTurnosFuturosPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosInscripcionContract;
use App\Modules\InscripcionesAtenciones\Contracts\TurnosFuturosPaqueteContract;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableAtencionExporter;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableComprobanteAtencionPdfExporter;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableComprobantePaquetePdfExporter;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableComprobantePdfExporter;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableInscripcionExporter;
use App\Modules\InscripcionesAtenciones\Contracts\UnavailableVigenciaExporter;
use App\Modules\InscripcionesAtenciones\Contracts\VigenciaEntidadContract;
use App\Modules\InscripcionesAtenciones\Contracts\VigenciaExporterContract;
use App\Modules\InscripcionesAtenciones\Events\AtencionRetirada;
use App\Modules\InscripcionesAtenciones\Events\AtencionUpdated;
use App\Modules\InscripcionesAtenciones\Events\DiagnosticoCreated;
use App\Modules\InscripcionesAtenciones\Events\EntrevistaCreated;
use App\Modules\InscripcionesAtenciones\Events\EntrevistaVinculada;
use App\Modules\InscripcionesAtenciones\Events\InscripcionCreated;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRenewed;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRetirada;
use App\Modules\InscripcionesAtenciones\Events\InscripcionUpdated;
use App\Modules\InscripcionesAtenciones\Events\PaqueteCreated;
use App\Modules\InscripcionesAtenciones\Events\PaqueteRenewed;
use App\Modules\InscripcionesAtenciones\Events\PaqueteRetirado;
use App\Modules\InscripcionesAtenciones\Events\PaqueteUpdated;
use App\Modules\InscripcionesAtenciones\Events\RenovacionMultipleEjecutada;
use App\Modules\InscripcionesAtenciones\Listeners\RecordAtencionAudit;
use App\Modules\InscripcionesAtenciones\Listeners\RecordInscripcionAudit;
use App\Modules\InscripcionesAtenciones\Listeners\RecordPaqueteAudit;
use App\Modules\InscripcionesAtenciones\Listeners\RecordRenovacionMultipleAudit;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 06 — Inscripciones y atenciones.
 *
 * Submódulos 01 (salas grupales), 02 (paquetes de sesiones individuales),
 * 03 (entrevistas y diagnósticos) y 04 (vigencias y renovaciones). Los
 * futuros submódulos reutilizan el prefijo, el provider y el catálogo de
 * auditoría.
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
        $this->app->bind(ConsumoPaqueteContract::class, StubConsumoPaqueteContract::class);
        $this->app->bind(ConsumoPaqueteBatchContract::class, StubConsumoPaqueteBatchContract::class);
        $this->app->bind(PagoPaqueteContract::class, StubPagoPaqueteContract::class);
        $this->app->bind(TurnosFuturosPaqueteContract::class, StubTurnosFuturosPaqueteContract::class);
        $this->app->bind(
            ComprobantePaquetePdfExporterContract::class,
            UnavailableComprobantePaquetePdfExporter::class,
        );
        $this->app->bind(PagoAtencionContract::class, StubPagoAtencionContract::class);
        $this->app->bind(
            ComprobanteAtencionPdfExporterContract::class,
            UnavailableComprobanteAtencionPdfExporter::class,
        );
        $this->app->bind(AtencionExporterContract::class, UnavailableAtencionExporter::class);
        $this->app->bind(VigenciaExporterContract::class, UnavailableVigenciaExporter::class);
        $this->app->bind(VigenciaEntidadContract::class, EloquentVigenciaEntidad::class);
    }

    public function boot(): void
    {
        foreach ([
            PaqueteCreated::class,
            PaqueteUpdated::class,
            PaqueteRenewed::class,
            PaqueteRetirado::class,
        ] as $event) {
            Event::listen($event, RecordPaqueteAudit::class);
        }

        foreach ([
            InscripcionCreated::class,
            InscripcionUpdated::class,
            InscripcionRenewed::class,
            InscripcionRetirada::class,
        ] as $event) {
            Event::listen($event, RecordInscripcionAudit::class);
        }

        foreach ([
            EntrevistaCreated::class,
            DiagnosticoCreated::class,
            AtencionUpdated::class,
            AtencionRetirada::class,
            EntrevistaVinculada::class,
        ] as $event) {
            Event::listen($event, RecordAtencionAudit::class);
        }

        Event::listen(RenovacionMultipleEjecutada::class, RecordRenovacionMultipleAudit::class);

        Route::middleware('api')
            ->prefix('api'.InscripcionesAtencionesModule::MODULE_URL)
            ->name('api.inscripciones-atenciones.')
            ->group(__DIR__.'/../routes.php');
    }
}
