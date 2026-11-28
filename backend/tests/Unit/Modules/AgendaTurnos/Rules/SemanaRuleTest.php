<?php

namespace Tests\Unit\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAgendaIncompletaException;
use App\Modules\AgendaTurnos\Exceptions\SemanaInvalidaException;
use App\Modules\AgendaTurnos\Rules\SemanaRule;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

final class SemanaRuleTest extends TestCase
{
    public function test_any_day_normalizes_to_monday_and_calculates_sunday_end(): void
    {
        $semana = (new SemanaRule)(
            '2026-11-25',
            CarbonImmutable::parse('2026-11-26 10:00:00', 'America/La_Paz'),
            [1, 2, 3, 4, 5, 6],
        );

        $this->assertSame('2026-11-23', $semana->inicio);
        $this->assertSame('2026-11-29', $semana->fin);
        $this->assertSame('2026-11-23', $semana->inicioVisible());
        $this->assertSame('2026-11-28', $semana->finVisible());
        $this->assertCount(7, $semana->dias);
        $this->assertFalse($semana->dias[6]->visible);
        $this->assertSame('Domingo', $semana->dias[6]->nombre());
    }

    public function test_sunday_normalizes_to_previous_monday(): void
    {
        $semana = (new SemanaRule)(
            '2026-11-29',
            CarbonImmutable::parse('2026-11-29 23:30:00', 'America/La_Paz'),
            [1, 2, 3, 4, 5, 6],
        );

        $this->assertSame('2026-11-23', $semana->inicio);
        $this->assertTrue($semana->dias[6]->esHoy);
    }

    public function test_monday_and_sunday_at_2330_and_0030_use_la_paz_timezone(): void
    {
        $sundayLate = CarbonImmutable::parse('2026-11-30 03:30:00', 'UTC');
        $mondayEarly = CarbonImmutable::parse('2026-11-30 04:30:00', 'UTC');

        $semanaSunday = (new SemanaRule)(
            null,
            $sundayLate,
            [1, 2, 3, 4, 5, 6],
            'America/La_Paz',
        );
        $semanaMonday = (new SemanaRule)(
            null,
            $mondayEarly,
            [1, 2, 3, 4, 5, 6],
            'America/La_Paz',
        );

        $this->assertSame('2026-11-23', $semanaSunday->inicio);
        $this->assertSame('2026-11-30', $semanaMonday->inicio);
    }

    public function test_sunday_can_be_visible_by_configuration(): void
    {
        $semana = (new SemanaRule)(
            '2026-11-23',
            CarbonImmutable::parse('2026-11-23 10:00:00', 'America/La_Paz'),
            [1, 2, 3, 4, 5, 6, 7],
        );

        $this->assertTrue($semana->dias[6]->visible);
        $this->assertSame('2026-11-29', $semana->finVisible());
    }

    public function test_invalid_date_is_rejected(): void
    {
        $this->expectException(SemanaInvalidaException::class);

        (new SemanaRule)(
            'no-es-una-fecha',
            CarbonImmutable::parse('2026-11-26 10:00:00', 'America/La_Paz'),
            [1, 2, 3, 4, 5, 6],
        );
    }

    public function test_enabled_days_must_be_valid_and_unique(): void
    {
        $this->expectException(ConfiguracionAgendaIncompletaException::class);

        (new SemanaRule)(
            '2026-11-23',
            CarbonImmutable::parse('2026-11-26 10:00:00', 'America/La_Paz'),
            [1, 1, 2],
        );
    }
}
