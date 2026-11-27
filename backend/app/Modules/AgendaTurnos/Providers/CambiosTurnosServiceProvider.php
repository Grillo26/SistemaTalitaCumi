<?php

namespace App\Modules\AgendaTurnos\Providers;

use App\Modules\AgendaTurnos\Contracts\CambiosTurnosExporterContract;
use App\Modules\AgendaTurnos\Contracts\UnavailableCambiosTurnosExporter;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\TurnoCancelado;
use App\Modules\AgendaTurnos\Events\TurnoReprogramado;
use App\Modules\AgendaTurnos\Events\TurnosCanceladosMasivamente;
use App\Modules\AgendaTurnos\Listeners\RecordCambiosTurnoAudit;
use App\Modules\AgendaTurnos\Support\AccionesTurnoRegistry;
use App\Modules\AgendaTurnos\Support\AccionTurnoDefinicion;
use App\Modules\AgendaTurnos\Support\CambiosTurnosModule;
use App\Modules\AgendaTurnos\Support\CambiosTurnosPermission;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Módulo 07 — Cambios de turnos (submódulo 03).
 *
 * Rutas, permisos en el registry de 7.1 y bitácora/historial de 7.3.
 */
final class CambiosTurnosServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            CambiosTurnosExporterContract::class,
            UnavailableCambiosTurnosExporter::class,
        );
    }

    public function boot(): void
    {
        foreach ([
            TurnoReprogramado::class,
            TurnoCancelado::class,
            TurnosCanceladosMasivamente::class,
        ] as $event) {
            Event::listen($event, RecordCambiosTurnoAudit::class);
        }

        $this->app->make(AccionesTurnoRegistry::class)->registrar(
            new AccionTurnoDefinicion(
                codigo: 'ver_historial',
                etiqueta: 'Ver historial',
                permiso: CambiosTurnosPermission::HISTORIAL,
                estadosPermitidos: EstadoTurno::cases(),
                metodo: 'GET',
                ruta: '/api'.CambiosTurnosModule::MODULE_URL.'/turnos/{id}/historial',
                orden: 10,
                repetible: true,
            ),
            new AccionTurnoDefinicion(
                codigo: 'reprogramar',
                etiqueta: 'Reprogramar',
                permiso: CambiosTurnosPermission::REPROGRAMAR,
                estadosPermitidos: [EstadoTurno::PROGRAMADO],
                metodo: 'POST',
                ruta: '/api'.CambiosTurnosModule::MODULE_URL.'/turnos/{id}/reprogramar',
                orden: 20,
            ),
            new AccionTurnoDefinicion(
                codigo: 'cancelar',
                etiqueta: 'Cancelar',
                permiso: CambiosTurnosPermission::CANCELAR,
                estadosPermitidos: [EstadoTurno::PROGRAMADO],
                metodo: 'POST',
                ruta: '/api'.CambiosTurnosModule::MODULE_URL.'/turnos/{id}/cancelar',
                orden: 30,
            ),
        );

        Route::middleware('api')
            ->prefix('api'.CambiosTurnosModule::MODULE_URL)
            ->name('api.agenda-turnos.cambios-turnos.')
            ->group(__DIR__.'/../routes-cambios.php');
    }
}
