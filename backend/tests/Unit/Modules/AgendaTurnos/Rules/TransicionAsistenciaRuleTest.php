<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\TransicionAsistenciaRule;
use PHPUnit\Framework\TestCase;

/**
 * 07.04/RN-001, 07.04/RN-005, 07.04/RF-008.
 */
final class TransicionAsistenciaRuleTest extends TestCase
{
    public function test_programado_accepts_initial_registration(): void
    {
        $rule = new TransicionAsistenciaRule;

        $this->assertSame([], $rule->errores(EstadoTurno::PROGRAMADO, EstadoTurno::ASISTIO, false));
        $this->assertSame([], $rule->errores(EstadoTurno::PROGRAMADO, EstadoTurno::FALTO, false));
    }

    public function test_attendance_states_accept_correction_but_not_initial_registration(): void
    {
        $rule = new TransicionAsistenciaRule;

        $this->assertSame([], $rule->errores(EstadoTurno::ASISTIO, EstadoTurno::FALTO, true));
        $this->assertSame([], $rule->errores(EstadoTurno::FALTO, EstadoTurno::ASISTIO, true));
        $this->assertNotSame([], $rule->errores(EstadoTurno::ASISTIO, EstadoTurno::FALTO, false));
    }

    public function test_cancelled_and_reprogrammed_are_blocked(): void
    {
        $rule = new TransicionAsistenciaRule;

        $this->assertNotSame([], $rule->errores(EstadoTurno::CANCELADO, EstadoTurno::ASISTIO, true));
        $this->assertNotSame([], $rule->errores(EstadoTurno::REPROGRAMADO, EstadoTurno::FALTO, true));
    }

    public function test_same_state_is_noop_and_never_returns_to_programado(): void
    {
        $rule = new TransicionAsistenciaRule;

        $this->assertTrue($rule->esNoOp(EstadoTurno::ASISTIO, EstadoTurno::ASISTIO));
        $this->assertFalse($rule->esNoOp(EstadoTurno::ASISTIO, EstadoTurno::FALTO));
        $this->assertFalse($rule->esNoOp(EstadoTurno::PROGRAMADO, EstadoTurno::PROGRAMADO));
        $this->assertNotSame([], $rule->errores(EstadoTurno::ASISTIO, EstadoTurno::PROGRAMADO, true));
    }
}
