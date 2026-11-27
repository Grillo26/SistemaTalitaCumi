<?php

namespace App\Modules\AgendaTurnos\Providers;

use App\Modules\AgendaTurnos\Contracts\AsistenciaPdfExporterContract;
use App\Modules\AgendaTurnos\Contracts\UnavailableAsistenciaPdfExporter;
use App\Modules\AgendaTurnos\Events\AsistenciaCorregida;
use App\Modules\AgendaTurnos\Events\AsistenciaRegistrada;
use App\Modules\AgendaTurnos\Listeners\RecordAsistenciaAudit;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 07 — Registro de asistencia por turno (submódulo 04).
 *
 * Rutas, bitácora/historial de asistencia y contrato de exportación PDF.
 */
final class AsistenciaTurnosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AsistenciaPdfExporterContract::class,
            UnavailableAsistenciaPdfExporter::class,
        );
    }

    public function boot(): void
    {
        foreach ([
            AsistenciaRegistrada::class,
            AsistenciaCorregida::class,
        ] as $event) {
            Event::listen($event, RecordAsistenciaAudit::class);
        }

        Route::middleware('api')
            ->prefix('api'.AsistenciaTurnosModule::MODULE_URL)
            ->name('api.agenda-turnos.asistencia.')
            ->group(__DIR__.'/../routes-asistencia.php');
    }
}
