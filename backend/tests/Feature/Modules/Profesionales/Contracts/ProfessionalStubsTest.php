<?php

namespace Tests\Feature\Modules\Profesionales\Contracts;

use App\Modules\CatalogoServicios\Contracts\EloquentProfessionalDiagnosticRequirements;
use App\Modules\Profesionales\Actions\Directorio\DeactivateProfesionalAction;
use App\Modules\Profesionales\Contracts\ProfessionalDiagnosticRequirementsContract;
use App\Modules\Profesionales\Contracts\ProfessionalExporterContract;
use App\Modules\Profesionales\Contracts\ProfessionalScheduleContract;
use App\Modules\Profesionales\Contracts\StubProfessionalScheduleContract;
use App\Modules\Profesionales\Contracts\UnavailableProfessionalExporter;
use Carbon\CarbonImmutable;
use LogicException;
use Tests\Feature\Modules\Profesionales\ProfesionalesTestCase;

final class ProfessionalStubsTest extends ProfesionalesTestCase
{
    public function test_schedule_contract_is_bound_to_clear_stub(): void
    {
        $contract = app(ProfessionalScheduleContract::class);

        $this->assertInstanceOf(StubProfessionalScheduleContract::class, $contract);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Módulo 07 — Agenda y turnos no implementado todavía.');

        $contract->haySolapamiento(1, CarbonImmutable::now(), CarbonImmutable::now()->addHour());
    }

    public function test_future_assignments_stub_is_explicit(): void
    {
        $contract = app(ProfessionalScheduleContract::class);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Módulo 07 — Agenda y turnos no implementado todavía.');

        $contract->tieneAsignacionesFuturas(1, CarbonImmutable::now());
    }

    public function test_diagnostic_requirements_are_provided_by_catalog(): void
    {
        $contract = app(ProfessionalDiagnosticRequirementsContract::class);

        $this->assertInstanceOf(EloquentProfessionalDiagnosticRequirements::class, $contract);
    }

    public function test_exporter_is_bound_to_unavailable_stub(): void
    {
        $contract = app(ProfessionalExporterContract::class);

        $this->assertInstanceOf(UnavailableProfessionalExporter::class, $contract);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Exportación PDF/Excel pendiente de infraestructura.');

        $contract->exportarDirectorio([]);
    }

    public function test_exporter_stub_covers_matrix_and_assignments(): void
    {
        $contract = app(ProfessionalExporterContract::class);

        foreach (['exportarMatriz', 'exportarAsignaciones'] as $method) {
            try {
                $contract->{$method}([]);
                $this->fail("Se esperaba LogicException en {$method}.");
            } catch (LogicException $exception) {
                $this->assertStringContainsString('Exportación PDF/Excel', $exception->getMessage());
            }
        }
    }

    public function test_professional_can_be_deactivated_without_calling_schedule_stub(): void
    {
        $profesional = $this->profesional(['version' => 1]);
        $actor = $this->userWithRole('superadmin');

        $result = app(DeactivateProfesionalAction::class)(
            $profesional,
            'Licencia',
            1,
            $actor,
        );

        $this->assertFalse((bool) $result->activo);
    }
}
