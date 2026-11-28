<?php

namespace App\Modules\Profesionales\Providers;

use App\Modules\AgendaTurnos\Contracts\EloquentProfessionalSchedule;
use App\Modules\Profesionales\Contracts\EloquentEspecialidadDirectory;
use App\Modules\Profesionales\Contracts\EloquentProfessionalAssignmentValidator;
use App\Modules\Profesionales\Contracts\EloquentProfessionalDirectory;
use App\Modules\Profesionales\Contracts\EspecialidadDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalAssignmentValidatorContract;
use App\Modules\Profesionales\Contracts\ProfessionalDiagnosticRequirementsContract;
use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use App\Modules\Profesionales\Contracts\ProfessionalExporterContract;
use App\Modules\Profesionales\Contracts\ProfessionalScheduleContract;
use App\Modules\Profesionales\Contracts\StubProfessionalDiagnosticRequirementsContract;
use App\Modules\Profesionales\Contracts\UnavailableProfessionalExporter;
use App\Modules\Profesionales\Events\Directorio\ProfesionalCreated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalEspecialidadesUpdated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalStatusChanged;
use App\Modules\Profesionales\Events\Directorio\ProfesionalSucursalChanged;
use App\Modules\Profesionales\Events\Directorio\ProfesionalUpdated;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadCreated;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadStatusChanged;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadUpdated;
use App\Modules\Profesionales\Listeners\InvalidateProfesionalValidatorCache;
use App\Modules\Profesionales\Listeners\RecordProfesionalesAudit;
use App\Modules\Profesionales\Support\ProfesionalesModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor del módulo 03 — Profesionales.
 *
 * Fase 1: directorio, catálogo de especialidades, sucursales de trabajo y
 * contrato de directorio. Fase 2: matriz, validador cacheado y filtros.
 * Fase 3: stubs de agenda, diagnósticos y exportación.
 */
final class ProfesionalesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ProfessionalDirectoryContract::class, EloquentProfessionalDirectory::class);
        $this->app->bind(EspecialidadDirectoryContract::class, EloquentEspecialidadDirectory::class);
        $this->app->bind(ProfessionalAssignmentValidatorContract::class, EloquentProfessionalAssignmentValidator::class);
        $this->app->bind(ProfessionalScheduleContract::class, EloquentProfessionalSchedule::class);
        $this->app->bind(ProfessionalDiagnosticRequirementsContract::class, StubProfessionalDiagnosticRequirementsContract::class);
        $this->app->bind(ProfessionalExporterContract::class, UnavailableProfessionalExporter::class);
    }

    public function boot(): void
    {
        foreach ([
            ProfesionalCreated::class,
            ProfesionalUpdated::class,
            ProfesionalEspecialidadesUpdated::class,
            ProfesionalStatusChanged::class,
            ProfesionalSucursalChanged::class,
            EspecialidadCreated::class,
            EspecialidadUpdated::class,
            EspecialidadStatusChanged::class,
        ] as $event) {
            Event::listen($event, RecordProfesionalesAudit::class);
            Event::listen($event, InvalidateProfesionalValidatorCache::class);
        }

        Route::middleware('api')
            ->prefix('api'.ProfesionalesModule::MODULE_URL)
            ->name('api.profesionales.directorio.')
            ->group(__DIR__.'/../routes-directorio.php');

        Route::middleware('api')
            ->prefix('api'.ProfesionalesModule::MODULE_URL)
            ->name('api.profesionales.especialidades.')
            ->group(__DIR__.'/../routes-especialidades.php');

        Route::middleware('api')
            ->prefix('api'.ProfesionalesModule::MODULE_URL)
            ->name('api.profesionales.matriz.')
            ->group(__DIR__.'/../routes-matriz.php');
    }
}
