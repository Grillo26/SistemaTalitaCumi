<?php

namespace Tests\Feature\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Contracts\DiagnosticTypeDirectoryContract;
use App\Modules\Pacientes\Contracts\PacienteExporterContract;
use App\Modules\Pacientes\Contracts\PatientEnrollmentDirectoryContract;
use App\Modules\Pacientes\Contracts\StubDiagnosticTypeDirectoryContract;
use App\Modules\Pacientes\Contracts\StubPatientEnrollmentDirectoryContract;
use App\Modules\Pacientes\Contracts\UnavailablePacienteExporter;
use LogicException;
use Tests\Feature\Modules\Pacientes\PacientesTestCase;

final class PacienteStubsTest extends PacientesTestCase
{
    public function test_diagnostic_stub_is_explicit(): void
    {
        $contract = app(DiagnosticTypeDirectoryContract::class);

        $this->assertInstanceOf(StubDiagnosticTypeDirectoryContract::class, $contract);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Módulo 05 — Catálogo de tipos de diagnóstico no implementado todavía.');

        $contract->existe(1);
    }

    public function test_enrollment_stub_is_explicit(): void
    {
        $contract = app(PatientEnrollmentDirectoryContract::class);

        $this->assertInstanceOf(StubPatientEnrollmentDirectoryContract::class, $contract);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Módulo 06 — Inscripciones no implementado todavía.');

        $contract->sucursalesConInscripcionVigente(1);
    }

    public function test_exporter_is_unavailable(): void
    {
        $contract = app(PacienteExporterContract::class);

        $this->assertInstanceOf(UnavailablePacienteExporter::class, $contract);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Exportación PDF/Excel pendiente de infraestructura.');

        $contract->exportarListado([], $this->actingAsSuperadmin());
    }

    public function test_diagnostic_id_is_rejected_without_catalog(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([
            'tipo_diagnostico_id' => 1,
        ], $branch))
            ->assertStatus(422)
            ->assertJsonPath('code', 'DIAGNOSTIC_CATALOG_UNAVAILABLE');
    }

    public function test_list_and_ficha_do_not_call_unavailable_stubs(): void
    {
        $branch = $this->branch();
        $user = $this->actingAsRole('recepcion');
        $this->withBranch($user, $branch);

        $created = $this->postJson('/api/pacientes/pacientes', $this->pacientePayload([], $branch))
            ->assertCreated()
            ->json('data');

        $this->getJson('/api/pacientes/pacientes')->assertOk();
        $this->getJson('/api/pacientes/pacientes/'.$created['id'])
            ->assertOk()
            ->assertJsonPath('data.sin_diagnostico_definido', true)
            ->assertJsonPath('data.tutores_disponibles', true);
    }
}
