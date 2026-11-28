<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\PorcentajeAsistenciaRule;
use PHPUnit\Framework\TestCase;

/**
 * 07.04/RF-009, 07.04/RF-015, 07.04/CA-008.
 */
final class PorcentajeAsistenciaRuleTest extends TestCase
{
    public function test_percentage_excludes_absences_from_the_denominator(): void
    {
        $rule = new PorcentajeAsistenciaRule;

        $this->assertSame(75.0, $rule->calcular(3, 1));
        $this->assertSame(100.0, $rule->calcular(3, 0));
        $this->assertSame(0.0, $rule->calcular(0, 3));
        $this->assertSame(0.0, $rule->calcular(0, 0));
        $this->assertSame(33.33, $rule->calcular(1, 2));
    }
}
