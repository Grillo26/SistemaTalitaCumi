<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAgendaIncompletaException;
use App\Modules\AgendaTurnos\Rules\RangoHorarioRule;
use PHPUnit\Framework\TestCase;

final class RangoHorarioRuleTest extends TestCase
{
    public function test_uses_configured_range_when_there_are_no_turns(): void
    {
        $rango = (new RangoHorarioRule)('08:00', '18:00', []);

        $this->assertSame('08:00', $rango->horaInicio);
        $this->assertSame('18:00', $rango->horaFin);
    }

    public function test_expands_range_to_include_turns_outside_configuration(): void
    {
        $rango = (new RangoHorarioRule)('08:00', '18:00', [
            ['hora_inicio' => '06:30', 'hora_fin' => '07:20'],
            ['hora_inicio' => '20:15', 'hora_fin' => '21:15'],
        ]);

        $this->assertSame('06:30', $rango->horaInicio);
        $this->assertSame('21:30', $rango->horaFin);
    }

    public function test_invalid_configuration_is_rejected(): void
    {
        $this->expectException(ConfiguracionAgendaIncompletaException::class);

        (new RangoHorarioRule)('19:00', '08:00', []);
    }
}
