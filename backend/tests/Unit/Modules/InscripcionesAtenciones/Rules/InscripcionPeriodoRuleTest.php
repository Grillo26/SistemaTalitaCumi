<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\PeriodoInscripcionInvalidoException;
use App\Modules\InscripcionesAtenciones\Rules\InscripcionPeriodoRule;
use PHPUnit\Framework\TestCase;

final class InscripcionPeriodoRuleTest extends TestCase
{
    public function test_accepts_a_closed_monthly_period(): void
    {
        InscripcionPeriodoRule::assertValido('2026-11-01', '2026-11-30');

        $this->assertTrue(true);
    }

    public function test_rejects_open_period(): void
    {
        $this->expectException(PeriodoInscripcionInvalidoException::class);

        InscripcionPeriodoRule::assertValido('2026-11-01', null);
    }

    public function test_rejects_end_before_start(): void
    {
        $this->expectException(PeriodoInscripcionInvalidoException::class);

        InscripcionPeriodoRule::assertValido('2026-11-30', '2026-11-01');
    }

    public function test_rejects_period_longer_than_one_month(): void
    {
        $this->expectException(PeriodoInscripcionInvalidoException::class);

        InscripcionPeriodoRule::assertValido('2026-11-01', '2026-12-02');
    }

    public function test_accepts_same_day_period_per_schema_rule(): void
    {
        InscripcionPeriodoRule::assertValido('2026-11-01', '2026-11-01');

        $this->assertTrue(true);
    }

    public function test_rejects_invalid_date_format(): void
    {
        $this->expectException(PeriodoInscripcionInvalidoException::class);

        InscripcionPeriodoRule::assertValido('01/11/2026', '30/11/2026');
    }
}
