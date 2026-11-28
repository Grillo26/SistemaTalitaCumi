<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\DiasHabilitadosRule;
use PHPUnit\Framework\TestCase;

/**
 * 07.05/RF-002 / 07.05/RN-010.
 */
final class DiasHabilitadosRuleTest extends TestCase
{
    private DiasHabilitadosRule $rule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule = new DiasHabilitadosRule;
    }

    public function test_lunes_a_sabado_habilitado_y_domingo_no(): void
    {
        $dias = [1, 2, 3, 4, 5, 6];

        $this->assertTrue($this->rule->habilitado('2025-12-01', $dias));
        $this->assertTrue($this->rule->habilitado('2025-12-06', $dias));
        $this->assertFalse($this->rule->habilitado('2025-12-07', $dias));
    }

    public function test_fecha_invalida_devuelve_error(): void
    {
        $this->assertNotSame([], $this->rule->errores('2025-13-40', [1, 2, 3]));
    }

    public function test_sin_dias_configurados_devuelve_error(): void
    {
        $this->assertNotSame([], $this->rule->errores('2025-12-01', []));
    }
}
