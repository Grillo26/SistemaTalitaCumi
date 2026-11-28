<?php

namespace App\Modules\AgendaTurnos\Providers;

use App\Modules\AgendaTurnos\Contracts\AgendaExporterContract;
use App\Modules\AgendaTurnos\Contracts\UnavailableAgendaExporter;
use App\Modules\AgendaTurnos\Events\FeriadoActualizado;
use App\Modules\AgendaTurnos\Events\FeriadoCreado;
use App\Modules\AgendaTurnos\Events\FeriadoDesactivado;
use App\Modules\AgendaTurnos\Events\ProfesionalAsignadoALote;
use App\Modules\AgendaTurnos\Events\TurnosGenerados;
use App\Modules\AgendaTurnos\Events\TurnosRegenerados;
use App\Modules\AgendaTurnos\Listeners\RecordAgendaGeneracionAudit;
use App\Modules\AgendaTurnos\Support\AccionesTurnoRegistry;
use App\Modules\AgendaTurnos\Support\AgendaTurnosModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 07 — Agenda y turnos.
 *
 * Este provider es la base del módulo padre. El submódulo 01 es de consulta;
 * 7.2 escribe turnos únicamente por generación y regeneración. 7.3 a 7.5
 * reutilizan el prefijo, el modelo Turno y el registry de acciones.
 */
final class AgendaTurnosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AgendaExporterContract::class, UnavailableAgendaExporter::class);
        $this->app->singleton(AccionesTurnoRegistry::class);
    }

    public function boot(): void
    {
        foreach ([
            TurnosGenerados::class,
            TurnosRegenerados::class,
            ProfesionalAsignadoALote::class,
            FeriadoCreado::class,
            FeriadoActualizado::class,
            FeriadoDesactivado::class,
        ] as $event) {
            Event::listen($event, RecordAgendaGeneracionAudit::class);
        }

        Route::middleware('api')
            ->prefix('api'.AgendaTurnosModule::MODULE_URL)
            ->name('api.agenda-turnos.agenda-semanal.')
            ->group(__DIR__.'/../routes.php');

        Route::middleware('api')
            ->prefix('api'.AgendaTurnosModule::MODULE_URL_PROGRAMACION)
            ->name('api.agenda-turnos.programacion-turnos.')
            ->group(__DIR__.'/../routes-programacion.php');
    }
}
