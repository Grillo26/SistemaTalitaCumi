<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Rules\EfectoSaldoRule;
use App\Modules\AgendaTurnos\Rules\EstadoEsperadoRule;
use App\Modules\AgendaTurnos\Rules\OrigenReprogramableRule;
use PHPUnit\Framework\TestCase;

/**
 * [Reprogramación y Cancelación] RF-008 / RN-006 / CA-005 / RT-001.
 */
final class EfectoSaldoYOrigenRuleTest extends TestCase
{
    public function test_saldo_consumes_only_when_the_motive_marks_it(): void
    {
        $rule = new EfectoSaldoRule;

        $this->assertTrue($rule->consume(true));
        $this->assertFalse($rule->consume(false));
        $this->assertFalse($rule->consume(null));
        $this->assertStringContainsString('no se descuenta', $rule->descripcion(null));
    }

    public function test_reprogramming_requires_an_active_origin_or_no_origin(): void
    {
        $rule = new OrigenReprogramableRule;

        $this->assertTrue($rule->permite(EstadoOrigenProgramacion::ACTIVA));
        $this->assertTrue($rule->permite(null));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::VENCIDA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::EN_PAUSA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::RENOVADA));
        $this->assertFalse($rule->permite(EstadoOrigenProgramacion::RETIRADA));
        $this->assertStringContainsString('vencida', (string) $rule->mensaje(EstadoOrigenProgramacion::VENCIDA));
    }

    public function test_expected_state_must_match(): void
    {
        $rule = new EstadoEsperadoRule;

        $this->assertTrue($rule->coincide(EstadoTurno::PROGRAMADO, EstadoTurno::PROGRAMADO));
        $this->assertFalse($rule->coincide(EstadoTurno::ASISTIO, EstadoTurno::PROGRAMADO));
        $this->assertStringContainsString('Recargá la ficha', $rule->mensaje(EstadoTurno::ASISTIO));
    }
}
