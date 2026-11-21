<?php

namespace App\Modules\Pacientes\Providers;

use App\Modules\Pacientes\Contracts\DiagnosticTypeDirectoryContract;
use App\Modules\Pacientes\Contracts\EloquentPatientDirectory;
use App\Modules\Pacientes\Contracts\EloquentTutorDirectory;
use App\Modules\Pacientes\Contracts\PacienteExporterContract;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use App\Modules\Pacientes\Contracts\PatientEnrollmentDirectoryContract;
use App\Modules\Pacientes\Contracts\StubDiagnosticTypeDirectoryContract;
use App\Modules\Pacientes\Contracts\StubPatientEnrollmentDirectoryContract;
use App\Modules\Pacientes\Contracts\TutorDirectoryContract;
use App\Modules\Pacientes\Contracts\UnavailablePacienteExporter;
use App\Modules\Pacientes\Events\Registro\PacienteCreated;
use App\Modules\Pacientes\Events\Registro\PacienteDuplicateConfirmed;
use App\Modules\Pacientes\Events\Registro\PacienteStatusChanged;
use App\Modules\Pacientes\Events\Registro\PacienteUpdated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorAssociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorDissociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorPrincipalChanged;
use App\Modules\Pacientes\Events\Tutores\TutorCreated;
use App\Modules\Pacientes\Events\Tutores\TutorUpdated;
use App\Modules\Pacientes\Listeners\RecordPacientesAudit;
use App\Modules\Pacientes\Support\PacientesModule;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor del módulo 04 — Pacientes y tutores.
 *
 * Fase 0 (04.02 núcleo): alta, búsqueda y asociación de tutores.
 * Fase 1 (04.01): registro de pacientes, estados, datos sensibles y bitácora.
 */
final class PacientesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(PatientDirectoryContract::class, EloquentPatientDirectory::class);
        $this->app->bind(TutorDirectoryContract::class, EloquentTutorDirectory::class);
        $this->app->bind(DiagnosticTypeDirectoryContract::class, StubDiagnosticTypeDirectoryContract::class);
        $this->app->bind(PatientEnrollmentDirectoryContract::class, StubPatientEnrollmentDirectoryContract::class);
        $this->app->bind(PacienteExporterContract::class, UnavailablePacienteExporter::class);
    }

    public function boot(): void
    {
        foreach ([
            PacienteCreated::class,
            PacienteUpdated::class,
            PacienteStatusChanged::class,
            PacienteDuplicateConfirmed::class,
            TutorCreated::class,
            TutorUpdated::class,
            PacienteTutorAssociated::class,
            PacienteTutorDissociated::class,
            PacienteTutorPrincipalChanged::class,
        ] as $event) {
            Event::listen($event, RecordPacientesAudit::class);
        }

        Route::middleware('api')
            ->prefix('api'.PacientesModule::MODULE_URL)
            ->name('api.pacientes.registro.')
            ->group(__DIR__.'/../routes-registro.php');

        Route::middleware('api')
            ->prefix('api'.PacientesModule::MODULE_URL)
            ->name('api.pacientes.tutores.')
            ->group(__DIR__.'/../routes-tutores.php');
    }
}
