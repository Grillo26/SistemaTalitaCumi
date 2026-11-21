<?php

namespace Tests\Unit\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Rules\PacienteOperationalStateRule;
use PHPUnit\Framework\TestCase;

final class PacienteOperationalStateRuleTest extends TestCase
{
    public function test_only_active_patients_can_be_inscribed_or_scheduled(): void
    {
        $rule = new PacienteOperationalStateRule;

        $this->assertTrue($rule->permiteInscripcion(EstadoPaciente::ACTIVO));
        $this->assertTrue($rule->permiteTurnoNuevo(EstadoPaciente::ACTIVO));

        foreach ([EstadoPaciente::EN_PAUSA, EstadoPaciente::RETIRADO] as $estado) {
            $this->assertFalse($rule->permiteInscripcion($estado));
            $this->assertFalse($rule->permiteTurnoNuevo($estado));
        }
    }
}
