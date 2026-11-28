<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\PatronDiasRule;
use PHPUnit\Framework\TestCase;

/**
 * [Programación de Turnos] RN-001 / RF-015.
 */
final class PatronDiasRuleTest extends TestCase
{
    private PatronDiasRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new PatronDiasRule;
    }

    public function test_accepts_a_valid_pattern(): void
    {
        $this->assertSame([], $this->rule->errores([1, 3, 6], '09:00', 50));
        $this->assertTrue($this->rule->esValido([2, 4], '23:10', 50));
    }

    public function test_rejects_empty_days(): void
    {
        $this->assertContains(
            'Seleccioná al menos un día de la semana.',
            $this->rule->errores([], '09:00', 50),
        );
    }

    public function test_rejects_sunday_and_out_of_range_days(): void
    {
        $this->assertContains(
            'Los días permitidos son de lunes a sábado.',
            $this->rule->errores([7], '09:00', 50),
        );
        $this->assertContains(
            'Los días permitidos son de lunes a sábado.',
            $this->rule->errores([0], '09:00', 50),
        );
    }

    public function test_rejects_duplicated_days(): void
    {
        $this->assertContains(
            'No se puede repetir un día de la semana.',
            $this->rule->errores([2, 2], '09:00', 50),
        );
    }

    public function test_rejects_invalid_time_and_duration(): void
    {
        $this->assertContains('La hora de inicio no es válida.', $this->rule->errores([1], '25:00', 50));
        $this->assertContains('La duración del turno debe ser mayor a cero.', $this->rule->errores([1], '09:00', 0));
    }

    public function test_rejects_a_turn_ending_after_midnight(): void
    {
        $this->assertContains(
            'El turno no puede terminar después de las 24:00.',
            $this->rule->errores([1], '23:30', 60),
        );
    }
}
