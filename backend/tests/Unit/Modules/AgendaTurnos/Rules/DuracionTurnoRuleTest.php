<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\DuracionTurnoRule;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RF-009 / RN-003 / RNF-011.
 */
final class DuracionTurnoRuleTest extends TestCase
{
    public function test_validates_positive_durations(): void
    {
        $rule = new DuracionTurnoRule;

        $this->assertTrue($rule->esValida(1));
        $this->assertTrue($rule->esValida(50));
        $this->assertFalse($rule->esValida(0));
        $this->assertFalse($rule->esValida(-10));
    }

    public function test_resolves_in_priority_order(): void
    {
        $rule = new DuracionTurnoRule;

        $this->assertSame(45, $rule->resolver(45, 50, 60));
        $this->assertSame(50, $rule->resolver(null, 50, 60));
        $this->assertSame(60, $rule->resolver(0, null, 60));
        $this->assertNull($rule->resolver(null, null, null));
    }
}
