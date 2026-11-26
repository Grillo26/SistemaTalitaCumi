<?php

namespace Tests\Unit\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TarifaCerradaException;
use App\Modules\CatalogoServicios\Exceptions\TarifaVigenciaInvalidaException;
use App\Modules\CatalogoServicios\Exceptions\TarifaYaCerradaException;
use App\Modules\CatalogoServicios\Rules\TarifaVigenciaRule;
use PHPUnit\Framework\TestCase;

final class TarifaVigenciaRuleTest extends TestCase
{
    public function test_valid_ranges(): void
    {
        TarifaVigenciaRule::assertValid('2026-01-01', null);
        TarifaVigenciaRule::assertValid('2026-01-01', '2026-12-31');
        TarifaVigenciaRule::assertValid('2026-01-01', '2026-01-01');

        $this->assertTrue(true);
    }

    public function test_end_date_cannot_be_before_start(): void
    {
        $this->expectException(TarifaVigenciaInvalidaException::class);
        TarifaVigenciaRule::assertValid('2026-02-01', '2026-01-31');
    }

    public function test_dates_must_be_iso(): void
    {
        $this->expectException(TarifaVigenciaInvalidaException::class);
        TarifaVigenciaRule::assertValid('01/02/2026', null);
    }

    public function test_closed_tariff_is_not_editable(): void
    {
        $this->expectException(TarifaCerradaException::class);
        TarifaVigenciaRule::assertEditable('2025-12-31', '2026-01-01');
    }

    public function test_open_or_current_tariff_is_editable(): void
    {
        TarifaVigenciaRule::assertEditable(null, '2026-01-01');
        TarifaVigenciaRule::assertEditable('2026-01-01', '2026-01-01');
        TarifaVigenciaRule::assertEditable('2026-12-31', '2026-01-01');

        $this->assertTrue(true);
    }

    public function test_cannot_close_an_already_closed_tariff(): void
    {
        $this->expectException(TarifaYaCerradaException::class);
        TarifaVigenciaRule::assertCanClose(
            '2026-12-31',
            '2026-12-31',
            '2026-01-01',
        );
    }

    public function test_close_date_must_be_after_start(): void
    {
        $this->expectException(TarifaVigenciaInvalidaException::class);
        TarifaVigenciaRule::assertCanClose(null, '2025-12-31', '2026-01-01');
    }
}
