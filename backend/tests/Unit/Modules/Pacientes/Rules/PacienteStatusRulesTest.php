<?php

namespace Tests\Unit\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Exceptions\PacienteStatusReasonRequiredException;
use App\Modules\Pacientes\Exceptions\PacienteStatusTransitionNotAllowedException;
use App\Modules\Pacientes\Rules\PacienteStatusChangeRule;
use App\Modules\Pacientes\Rules\PacienteStatusTransitionRule;
use PHPUnit\Framework\TestCase;

final class PacienteStatusRulesTest extends TestCase
{
    public function test_allowed_transitions(): void
    {
        $this->assertTrue(EstadoPaciente::ACTIVO->canTransitionTo(EstadoPaciente::EN_PAUSA));
        $this->assertTrue(EstadoPaciente::ACTIVO->canTransitionTo(EstadoPaciente::RETIRADO));
        $this->assertTrue(EstadoPaciente::EN_PAUSA->canTransitionTo(EstadoPaciente::ACTIVO));
        $this->assertTrue(EstadoPaciente::EN_PAUSA->canTransitionTo(EstadoPaciente::RETIRADO));
        $this->assertTrue(EstadoPaciente::RETIRADO->canTransitionTo(EstadoPaciente::ACTIVO));

        $this->assertFalse(EstadoPaciente::RETIRADO->canTransitionTo(EstadoPaciente::EN_PAUSA));
    }

    public function test_invalid_transition_throws(): void
    {
        $this->expectException(PacienteStatusTransitionNotAllowedException::class);

        (new PacienteStatusTransitionRule)->assertAllowed(
            EstadoPaciente::RETIRADO,
            EstadoPaciente::EN_PAUSA,
        );
    }

    public function test_reason_is_required_for_retirement_and_return(): void
    {
        $rule = new PacienteStatusChangeRule;

        $this->expectException(PacienteStatusReasonRequiredException::class);

        $rule->reason(EstadoPaciente::ACTIVO, EstadoPaciente::RETIRADO, '');
    }

    public function test_reason_is_required_for_pause(): void
    {
        $rule = new PacienteStatusChangeRule;

        $this->expectException(PacienteStatusReasonRequiredException::class);

        $rule->reason(EstadoPaciente::ACTIVO, EstadoPaciente::EN_PAUSA, null);
    }

    public function test_reason_is_optional_when_returning_from_pause(): void
    {
        $rule = new PacienteStatusChangeRule;

        $this->assertNull($rule->reason(EstadoPaciente::EN_PAUSA, EstadoPaciente::ACTIVO, null));
        $this->assertSame('Mejoró', $rule->reason(EstadoPaciente::EN_PAUSA, EstadoPaciente::ACTIVO, 'Mejoró'));
    }
}
