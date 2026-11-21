<?php

namespace Tests\Unit\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Enums\MotivoNoAsignable;
use App\Modules\Profesionales\Exceptions\DuplicateProfesionalDocumentException;
use App\Modules\Profesionales\Exceptions\ProfesionalEspecialidadInvalidException;
use App\Modules\Profesionales\Exceptions\StatusReasonRequiredException;
use App\Modules\Profesionales\Exceptions\StatusUnchangedException;
use App\Modules\Profesionales\Rules\ProfesionalAssignabilityRule;
use App\Modules\Profesionales\Rules\ProfesionalDocumentUniquenessRule;
use App\Modules\Profesionales\Rules\ProfesionalIdentityRule;
use App\Modules\Profesionales\Rules\ProfesionalSpecialtyAssignmentRule;
use App\Modules\Profesionales\Rules\ProfesionalStatusChangeRule;
use PHPUnit\Framework\TestCase;

final class ProfesionalRulesTest extends TestCase
{
    public function test_identity_normalization(): void
    {
        $this->assertSame('Ana María Profesional', ProfesionalIdentityRule::normalizeName('  Ana   María  Profesional '));
        $this->assertSame('12345678-1A', ProfesionalIdentityRule::normalizeCi(' 12345678-1a '));
    }

    public function test_document_uniqueness_rule(): void
    {
        $rule = new ProfesionalDocumentUniquenessRule;
        $rule->assertAvailable('123', null);

        $this->expectException(DuplicateProfesionalDocumentException::class);
        $rule->assertAvailable('123', 7);
    }

    public function test_status_change_requires_reason_and_different_state(): void
    {
        $rule = new ProfesionalStatusChangeRule;

        $this->assertSame('Motivo', $rule->reason('  Motivo ', true));

        try {
            $rule->reason('   ', false);
            $this->fail('Se esperaba StatusReasonRequiredException.');
        } catch (StatusReasonRequiredException) {
            $this->assertTrue(true);
        }

        $this->expectException(StatusUnchangedException::class);
        $rule->assertDifferent(true, true);
    }

    public function test_specialty_assignment_requires_exactly_one_principal(): void
    {
        $rule = new ProfesionalSpecialtyAssignmentRule;

        $rule->assertValid([
            ['especialidad_id' => 1, 'tipo' => 'principal'],
        ], false);

        $this->expectException(ProfesionalEspecialidadInvalidException::class);
        $rule->assertValid([
            ['especialidad_id' => 1, 'tipo' => 'principal'],
            ['especialidad_id' => 2, 'tipo' => 'principal'],
        ], false);
    }

    public function test_specialty_assignment_rejects_duplicates_and_disabled_secondaries(): void
    {
        $rule = new ProfesionalSpecialtyAssignmentRule;

        try {
            $rule->assertValid([
                ['especialidad_id' => 1, 'tipo' => 'principal'],
                ['especialidad_id' => 1, 'tipo' => 'secundaria'],
            ], true);
            $this->fail('Se esperaba duplicado.');
        } catch (ProfesionalEspecialidadInvalidException) {
            $this->assertTrue(true);
        }

        $this->expectException(ProfesionalEspecialidadInvalidException::class);
        $rule->assertValid([
            ['especialidad_id' => 1, 'tipo' => 'principal'],
            ['especialidad_id' => 2, 'tipo' => 'secundaria'],
        ], false);
    }

    public function test_assignability_reasons(): void
    {
        $this->assertSame([], ProfesionalAssignabilityRule::reasons(true, true, true, true, false));

        $reasons = ProfesionalAssignabilityRule::reasons(false, true, true, false, false);

        $this->assertContains(MotivoNoAsignable::PROFESIONAL_INACTIVO, $reasons);
        $this->assertContains(MotivoNoAsignable::SIN_SUCURSAL_ACTIVA, $reasons);

        $reasons = ProfesionalAssignabilityRule::reasons(true, false, false, false, true);
        $this->assertContains(MotivoNoAsignable::SIN_ESPECIALIDAD_ACTIVA, $reasons);
        $this->assertContains(MotivoNoAsignable::SUCURSAL_INACTIVA, $reasons);
    }
}
