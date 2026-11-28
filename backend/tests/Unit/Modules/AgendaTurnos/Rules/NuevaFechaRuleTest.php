<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Rules\NuevaFechaRule;
use PHPUnit\Framework\TestCase;

/**
 * [Reprogramación y Cancelación] RF-001 / RN-005 / CA-001.
 */
final class NuevaFechaRuleTest extends TestCase
{
    private NuevaFechaRule $rule;

    protected function setUp(): void
    {
        parent::setUp();
        $this->rule = new NuevaFechaRule;
    }

    public function test_accepts_a_future_date_inside_the_origin_period(): void
    {
        $this->assertSame([], $this->rule->errores(
            '2025-12-10',
            '09:00',
            '2025-11-30',
            '10:00',
            '2025-12-01',
            '2025-12-31',
            50,
        ));
    }

    public function test_rejects_past_and_today_before_the_current_time(): void
    {
        $this->assertContains('La nueva fecha y hora deben ser futuras.', $this->rule->errores(
            '2025-11-29',
            '09:00',
            '2025-11-30',
            '10:00',
            null,
            null,
            50,
        ));
        $this->assertContains('La nueva fecha y hora deben ser futuras.', $this->rule->errores(
            '2025-11-30',
            '09:00',
            '2025-11-30',
            '10:00',
            null,
            null,
            50,
        ));
    }

    public function test_rejects_dates_outside_the_origin_period(): void
    {
        $errores = $this->rule->errores(
            '2026-01-02',
            '09:00',
            '2025-11-30',
            '10:00',
            '2025-12-01',
            '2025-12-31',
            50,
        );

        $this->assertContains('La nueva fecha supera el fin de la vigencia de la entidad origen.', $errores);
    }

    public function test_rejects_a_turn_ending_after_midnight(): void
    {
        $this->assertContains('El turno no puede terminar después de las 24:00.', $this->rule->errores(
            '2025-12-10',
            '23:30',
            '2025-11-30',
            '10:00',
            null,
            null,
            60,
        ));
    }

    public function test_detects_the_same_slot_and_calculates_end_time(): void
    {
        $this->assertTrue($this->rule->esMismoSlot('2025-12-02', '09:00', '2025-12-02', '09:00'));
        $this->assertFalse($this->rule->esMismoSlot('2025-12-02', '09:30', '2025-12-02', '09:00'));
        $this->assertSame('09:50', $this->rule->horaFin('09:00', 50));
    }
}
