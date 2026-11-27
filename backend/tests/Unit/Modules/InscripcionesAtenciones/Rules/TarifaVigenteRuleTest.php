<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\CatalogoServicios\Contracts\TarifaAplicableResumen;
use App\Modules\InscripcionesAtenciones\Exceptions\TarifaNoVigenteException;
use App\Modules\InscripcionesAtenciones\Rules\TarifaVigenteRule;
use PHPUnit\Framework\TestCase;

final class TarifaVigenteRuleTest extends TestCase
{
    public function test_accepts_tariff_vigent_at_start_date(): void
    {
        TarifaVigenteRule::assertVigente($this->tarifa('2026-01-01', '2026-12-31'), '2026-11-01');

        $this->assertTrue(true);
    }

    public function test_accepts_open_ended_tariff(): void
    {
        TarifaVigenteRule::assertVigente($this->tarifa('2026-01-01', null), '2026-11-01');

        $this->assertTrue(true);
    }

    public function test_rejects_tariff_not_started(): void
    {
        $this->expectException(TarifaNoVigenteException::class);

        TarifaVigenteRule::assertVigente($this->tarifa('2026-12-01', null), '2026-11-01');
    }

    public function test_rejects_closed_tariff(): void
    {
        $this->expectException(TarifaNoVigenteException::class);

        TarifaVigenteRule::assertVigente($this->tarifa('2026-01-01', '2026-10-31'), '2026-11-01');
    }

    public function test_rejects_missing_tariff(): void
    {
        $this->expectException(TarifaNoVigenteException::class);

        TarifaVigenteRule::assertVigente(null, '2026-11-01');
    }

    private function tarifa(string $desde, ?string $hasta): TarifaAplicableResumen
    {
        return new TarifaAplicableResumen(
            tarifaId: 1,
            servicioId: 1,
            servicioNombre: 'Sala grupal',
            categoriaTarifaId: 1,
            categoriaNombre: 'General',
            sucursalId: 1,
            sucursalNombre: 'Central',
            monto: '500.00',
            vigenteDesde: $desde,
            vigenteHasta: $hasta,
            origen: 'sucursal',
        );
    }
}
