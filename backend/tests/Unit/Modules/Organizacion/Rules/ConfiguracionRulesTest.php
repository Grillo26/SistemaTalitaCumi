<?php

namespace Tests\Unit\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Rules\ParameterCoherenceRule;
use App\Modules\Organizacion\Rules\ParameterFixedValueRule;
use App\Modules\Organizacion\Rules\ParameterRangeRule;
use App\Modules\Organizacion\Rules\SensitiveParameterRule;
use App\Modules\Organizacion\Support\ParameterValueCaster;
use PHPUnit\Framework\TestCase;

final class ConfiguracionRulesTest extends TestCase
{
    public function test_caster_normalizes_each_type(): void
    {
        $this->assertSame('45', ParameterValueCaster::normalize(ParameterType::ENTERO, ' 45 '));
        $this->assertSame('45.5', ParameterValueCaster::normalize(ParameterType::DECIMAL, '45.500'));
        $this->assertSame('08:00', ParameterValueCaster::normalize(ParameterType::HORA, '08:00:00'));
        $this->assertSame('1', ParameterValueCaster::normalize(ParameterType::BOOLEANO, true));
        $this->assertSame('0', ParameterValueCaster::normalize(ParameterType::BOOLEANO, 'false'));
        $this->assertSame('Hola', ParameterValueCaster::normalize(ParameterType::TEXTO, ' Hola '));
    }

    public function test_caster_rejects_invalid_type_values(): void
    {
        $this->assertFalse(ParameterValueCaster::isValid(ParameterType::ENTERO, '45.5'));
        $this->assertFalse(ParameterValueCaster::isValid(ParameterType::DECIMAL, 'abc'));
        $this->assertFalse(ParameterValueCaster::isValid(ParameterType::HORA, '25:00'));
        $this->assertFalse(ParameterValueCaster::isValid(ParameterType::BOOLEANO, 'tal vez'));
    }

    public function test_caster_keeps_decimal_precision_as_string(): void
    {
        $this->assertSame('123456789.10', ParameterValueCaster::cast(ParameterType::DECIMAL, '123456789.10'));
    }

    public function test_range_rejects_values_outside_limits(): void
    {
        $this->assertNotNull(
            ParameterRangeRule::error(ParameterType::ENTERO, 20, '30', '90', 'minutos'),
        );
        $this->assertNull(
            ParameterRangeRule::error(ParameterType::ENTERO, 50, '30', '90', 'minutos'),
        );
        $this->assertNotNull(
            ParameterRangeRule::error(ParameterType::DECIMAL, '9.99', '10', '20'),
        );
        $this->assertNotNull(
            ParameterRangeRule::error(ParameterType::HORA, '07:30', '08:00', '18:00'),
        );
        $this->assertNull(
            ParameterRangeRule::error(ParameterType::HORA, '08:00', '08:00', '18:00'),
        );
    }

    public function test_range_uses_text_length_for_text_parameters(): void
    {
        $this->assertNotNull(
            ParameterRangeRule::error(ParameterType::TEXTO, 'abc', null, '2'),
        );
        $this->assertNull(
            ParameterRangeRule::error(ParameterType::TEXTO, 'abc', null, '3'),
        );
    }

    public function test_coherence_rejects_end_before_start(): void
    {
        $error = ParameterCoherenceRule::check(
            [
                'agenda.turno_manana.inicio' => '08:00',
                'agenda.turno_manana.fin' => '07:30',
            ],
            'agenda.turno_manana.fin',
            '07:30',
        );

        $this->assertNotNull($error);
        $this->assertStringContainsString('posterior', (string) $error);
    }

    public function test_coherence_rejects_close_before_open(): void
    {
        $error = ParameterCoherenceRule::check(
            [
                'agenda.horario_apertura' => '19:00',
                'agenda.horario_cierre' => '07:00',
            ],
            'agenda.horario_cierre',
            '07:00',
        );

        $this->assertNotNull($error);
    }

    public function test_coherence_rejects_session_longer_than_block(): void
    {
        $error = ParameterCoherenceRule::check(
            [
                'duracion_sesion_minutos' => '90',
                'agenda.turno_manana.inicio' => '08:00',
                'agenda.turno_manana.fin' => '09:00',
                'agenda.turno_tarde.inicio' => '14:00',
                'agenda.turno_tarde.fin' => '18:00',
            ],
            'duracion_sesion_minutos',
            '90',
        );

        $this->assertNotNull($error);
        $this->assertStringContainsString('bloque de agenda', (string) $error);
    }

    public function test_coherence_accepts_valid_agenda(): void
    {
        $error = ParameterCoherenceRule::check(
            [
                'duracion_sesion_minutos' => '50',
                'agenda.horario_apertura' => '07:00',
                'agenda.horario_cierre' => '19:00',
                'agenda.turno_manana.inicio' => '08:00',
                'agenda.turno_manana.fin' => '12:00',
                'agenda.turno_tarde.inicio' => '14:00',
                'agenda.turno_tarde.fin' => '18:00',
            ],
            'duracion_sesion_minutos',
            '50',
        );

        $this->assertNull($error);
    }

    public function test_sensitive_parameter_requires_confirmation_and_reason(): void
    {
        $this->assertNotNull(SensitiveParameterRule::error(true, false, 'motivo'));
        $this->assertNotNull(SensitiveParameterRule::error(true, true, '   '));
        $this->assertNull(SensitiveParameterRule::error(true, true, 'Ajuste aprobado'));
        $this->assertNull(SensitiveParameterRule::error(false, false, null));
    }

    public function test_fixed_values_are_enforced(): void
    {
        $this->assertTrue(ParameterFixedValueRule::isFixed('moneda'));
        $this->assertTrue(ParameterFixedValueRule::matches('moneda', 'BOB'));
        $this->assertFalse(ParameterFixedValueRule::matches('moneda', 'USD'));
        $this->assertTrue(ParameterFixedValueRule::matches(
            'comprobante.leyenda_no_factura',
            'Este documento NO es factura',
        ));
    }
}
