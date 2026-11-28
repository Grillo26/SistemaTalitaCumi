<?php

namespace App\Modules\AgendaTurnos\Providers;

use App\Modules\AgendaTurnos\Contracts\DiagnosticosPdfExporterContract;
use App\Modules\AgendaTurnos\Contracts\UnavailableDiagnosticosPdfExporter;
use App\Modules\AgendaTurnos\Events\DiagnosticoCancelado;
use App\Modules\AgendaTurnos\Events\EstadoGeneralDiagnosticoCambiado;
use App\Modules\AgendaTurnos\Events\TurnoDiagnosticoProgramado;
use App\Modules\AgendaTurnos\Listeners\RecordDiagnosticoAudit;
use App\Modules\AgendaTurnos\Support\DiagnosticosModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 07 — Diagnóstico con varios profesionales (submódulo 05).
 *
 * Rutas, bitácora y contrato de exportación PDF. La cancelación reutiliza la
 * Action de 07.3 y la asistencia/reprogramación siguen perteneciendo a 07.4 y
 * 07.3 respectivamente.
 */
final class DiagnosticosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            DiagnosticosPdfExporterContract::class,
            UnavailableDiagnosticosPdfExporter::class,
        );
    }

    public function boot(): void
    {
        foreach ([
            TurnoDiagnosticoProgramado::class,
            EstadoGeneralDiagnosticoCambiado::class,
            DiagnosticoCancelado::class,
        ] as $event) {
            Event::listen($event, RecordDiagnosticoAudit::class);
        }

        Route::middleware('api')
            ->prefix('api'.DiagnosticosModule::MODULE_URL)
            ->name('api.agenda-turnos.diagnosticos.')
            ->group(__DIR__.'/../routes-diagnosticos.php');
    }
}
