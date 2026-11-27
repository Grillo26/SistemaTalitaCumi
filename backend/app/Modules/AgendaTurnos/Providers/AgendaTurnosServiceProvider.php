<?php

namespace App\Modules\AgendaTurnos\Providers;

use App\Modules\AgendaTurnos\Contracts\AgendaExporterContract;
use App\Modules\AgendaTurnos\Contracts\UnavailableAgendaExporter;
use App\Modules\AgendaTurnos\Support\AccionesTurnoRegistry;
use App\Modules\AgendaTurnos\Support\AgendaTurnosModule;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 07 — Agenda y turnos.
 *
 * Este provider es la base del módulo padre. El submódulo 01 es de consulta;
 * 7.2 a 7.5 reutilizan el prefijo, el modelo Turno y el registry de acciones.
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
        Route::middleware('api')
            ->prefix('api'.AgendaTurnosModule::MODULE_URL)
            ->name('api.agenda-turnos.agenda-semanal.')
            ->group(__DIR__.'/../routes.php');
    }
}
