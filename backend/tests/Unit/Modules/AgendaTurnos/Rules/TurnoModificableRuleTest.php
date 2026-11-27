<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\TurnoModificableRule;
use PHPUnit\Framework\TestCase;

/**
 * [Reprogramación y Cancelación] RN-003 / RF-007.
 */
final class TurnoModificableRuleTest extends TestCase
{
    public function test_only_programado_is_modifiable(): void
    {
        $rule = new TurnoModificableRule;

        $this->assertTrue($rule->permite(EstadoTurno::PROGRAMADO));
        $this->assertFalse($rule->permite(EstadoTurno::ASISTIO));
        $this->assertFalse($rule->permite(EstadoTurno::FALTO));
        $this->assertFalse($rule->permite(EstadoTurno::CANCELADO));
        $this->assertFalse($rule->permite(EstadoTurno::REPROGRAMADO));
    }

    public function test_messages_are_specific_for_reprogramming_and_cancelling(): void
    {
        $rule = new TurnoModificableRule;

        $this->assertSame(
            'No se puede reprogramar un turno ya registrado.',
            $rule->mensaje(EstadoTurno::ASISTIO, 'reprogramar'),
        );
        $this->assertSame(
            'No se puede cancelar un turno ya registrado.',
            $rule->mensaje(EstadoTurno::FALTO, 'cancelar'),
        );
        $this->assertSame(
            'El turno ya está cancelado.',
            $rule->mensaje(EstadoTurno::CANCELADO, 'cancelar'),
        );
        $this->assertStringContainsString(
            'Gestioná el turno vigente',
            (string) $rule->mensaje(EstadoTurno::REPROGRAMADO, 'reprogramar'),
        );
    }
}
