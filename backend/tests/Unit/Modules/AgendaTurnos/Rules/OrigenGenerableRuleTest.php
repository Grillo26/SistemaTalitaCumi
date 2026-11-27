<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Rules\OrigenGenerableRule;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RF-002 / RF-010 / CA-005 / CU-03 FE-01.
 */
final class OrigenGenerableRuleTest extends TestCase
{
    public function test_only_active_origins_are_generable(): void
    {
        $rule = new OrigenGenerableRule;

        $this->assertTrue($rule->permite(EstadoOrigenProgramacion::ACTIVA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::VENCIDA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::EN_PAUSA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::RENOVADA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::RETIRADA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::DESCONOCIDO));
    }

    public function test_messages_suggest_the_next_action(): void
    {
        $rule = new OrigenGenerableRule;

        $this->assertNull($rule->mensaje(
            EstadoOrigenProgramacion::ACTIVA,
            TipoOrigenTurno::PAQUETE_SESIONES,
        ));
        $this->assertStringContainsString('vencido', (string) $rule->mensaje(
            EstadoOrigenProgramacion::VENCIDA,
            TipoOrigenTurno::PAQUETE_SESIONES,
        ));
        $this->assertStringContainsString('Renovelo', (string) $rule->mensaje(
            EstadoOrigenProgramacion::VENCIDA,
            TipoOrigenTurno::PAQUETE_SESIONES,
        ));
        $this->assertStringContainsString('pausa', (string) $rule->mensaje(
            EstadoOrigenProgramacion::EN_PAUSA,
            TipoOrigenTurno::INSCRIPCION_SALA,
        ));
        $this->assertStringContainsString('retirado', (string) $rule->mensaje(
            EstadoOrigenProgramacion::RETIRADA,
            TipoOrigenTurno::PAQUETE_SESIONES,
        ));
    }
}
