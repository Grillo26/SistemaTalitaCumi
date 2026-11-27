<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoVigencia;
use App\Modules\InscripcionesAtenciones\Enums\MotivoNoRenovable;
use App\Modules\InscripcionesAtenciones\Exceptions\CambioTarifaSinConfirmarException;
use App\Modules\InscripcionesAtenciones\Exceptions\RenovacionNoPermitidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\VentanaAlertaInvalidaException;
use App\Modules\InscripcionesAtenciones\Rules\CambioTarifaRule;
use App\Modules\InscripcionesAtenciones\Rules\RenovacionElegibleRule;
use App\Modules\InscripcionesAtenciones\Rules\VentanaAlertaRule;
use App\Modules\InscripcionesAtenciones\Rules\VigenciaClassifierRule;
use PHPUnit\Framework\TestCase;

final class VigenciaRulesTest extends TestCase
{
    public function test_classifier_boundaries_and_precedence(): void
    {
        $hoy = '2026-11-25';

        $this->assertSame(
            EstadoVigencia::VENCIDO,
            VigenciaClassifierRule::classify('2026-11-24', $hoy, 7, false, false),
        );
        $this->assertSame(
            EstadoVigencia::POR_VENCER,
            VigenciaClassifierRule::classify('2026-11-25', $hoy, 7, false, false),
        );
        $this->assertSame(
            EstadoVigencia::POR_VENCER,
            VigenciaClassifierRule::classify('2026-12-02', $hoy, 7, false, false),
        );
        $this->assertSame(
            EstadoVigencia::VIGENTE,
            VigenciaClassifierRule::classify('2026-12-03', $hoy, 7, false, false),
        );
        $this->assertSame(
            EstadoVigencia::RETIRADO,
            VigenciaClassifierRule::classify('2026-11-24', $hoy, 7, true, false),
        );
        $this->assertSame(
            EstadoVigencia::RENOVADO,
            VigenciaClassifierRule::classify('2026-11-24', $hoy, 7, false, true),
        );
        $this->assertSame(
            EstadoVigencia::POR_VENCER,
            VigenciaClassifierRule::classify('2026-11-20', '2026-11-15', 7, false, false),
        );
    }

    public function test_window_rule_accepts_only_supported_range(): void
    {
        VentanaAlertaRule::assertValida(1);
        VentanaAlertaRule::assertValida(365);

        $this->expectException(VentanaAlertaInvalidaException::class);
        VentanaAlertaRule::assertValida(366);
    }

    public function test_zero_window_is_invalid(): void
    {
        $this->expectException(VentanaAlertaInvalidaException::class);
        VentanaAlertaRule::assertValida(0);
    }

    public function test_renewal_eligibility_reasons(): void
    {
        $this->assertNull(RenovacionElegibleRule::motivo(true, true, true, false, false));
        $this->assertSame(
            MotivoNoRenovable::ENTIDAD_RETIRADA,
            RenovacionElegibleRule::motivo(true, true, true, true, false),
        );
        $this->assertSame(
            MotivoNoRenovable::ENTIDAD_RENOVADA,
            RenovacionElegibleRule::motivo(true, true, true, false, true),
        );
        $this->assertSame(
            MotivoNoRenovable::PACIENTE_INACTIVO,
            RenovacionElegibleRule::motivo(false, true, true, false, false),
        );
        $this->assertSame(
            MotivoNoRenovable::SERVICIO_INACTIVO,
            RenovacionElegibleRule::motivo(true, false, true, false, false),
        );
        $this->assertSame(
            MotivoNoRenovable::SALA_INACTIVA,
            RenovacionElegibleRule::motivo(true, true, false, false, false),
        );

        $this->expectException(RenovacionNoPermitidaException::class);
        RenovacionElegibleRule::assertElegible(true, true, true, true, false);
    }

    public function test_tariff_change_compares_id_and_amount(): void
    {
        $this->assertFalse(CambioTarifaRule::hayCambio(1, '400.00', 1, '400.00'));
        $this->assertTrue(CambioTarifaRule::hayCambio(1, '400.00', 2, '400.00'));
        $this->assertTrue(CambioTarifaRule::hayCambio(1, '400.00', 1, '450.00'));

        CambioTarifaRule::assertMontoVisto('450.00', '450.00', true, true, ['tarifa_id' => 1]);

        $this->expectException(CambioTarifaSinConfirmarException::class);
        CambioTarifaRule::assertMontoVisto('400.00', '450.00', false, true, ['tarifa_id' => 1]);
    }
}
