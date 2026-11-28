<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\VentanaEdicionAsistenciaRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

/**
 * 07.04/RF-004, 07.04/RN-004, 07.04/CA-005, 07.04/CA-006.
 */
final class VentanaEdicionAsistenciaRuleTest extends TestCase
{
    public function test_inside_window_includes_the_exact_boundary(): void
    {
        $rule = new VentanaEdicionAsistenciaRule;
        $registrada = CarbonImmutable::parse('2025-11-30 10:00:00', 'America/La_Paz');

        $this->assertTrue($rule->dentro(
            $registrada,
            $registrada->addHours(24),
            24,
        ));
        $this->assertFalse($rule->dentro(
            $registrada,
            $registrada->addHours(24)->addSecond(),
            24,
        ));
    }

    public function test_zero_means_no_window(): void
    {
        $rule = new VentanaEdicionAsistenciaRule;
        $registrada = CarbonImmutable::parse('2025-11-30 10:00:00', 'America/La_Paz');

        $resultado = $rule->evaluar($registrada, $registrada, 0);

        $this->assertFalse($resultado['dentro']);
        $this->assertSame('ventana_cerrada', $resultado['motivo']);
    }

    public function test_without_registration_there_is_no_window(): void
    {
        $resultado = (new VentanaEdicionAsistenciaRule)->evaluar(
            null,
            CarbonImmutable::now('America/La_Paz'),
            24,
        );

        $this->assertFalse($resultado['dentro']);
        $this->assertSame('sin_registro', $resultado['motivo']);
    }
}
