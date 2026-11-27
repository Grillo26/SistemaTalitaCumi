<?php

namespace Tests\Unit\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\EstadoPaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaPaquete;
use App\Modules\InscripcionesAtenciones\Exceptions\CantidadSesionesInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\DiasSemanaInvalidosException;
use App\Modules\InscripcionesAtenciones\Exceptions\DuracionSesionInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\HoraInvalidaException;
use App\Modules\InscripcionesAtenciones\Exceptions\MotivoBajaPaqueteInvalidoException;
use App\Modules\InscripcionesAtenciones\Exceptions\ObservacionesInvalidasException;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteDatosIncompletosException;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteNoGestionableException;
use App\Modules\InscripcionesAtenciones\Exceptions\PaquetePeriodoInvalidoException;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteRetiradoException;
use App\Modules\InscripcionesAtenciones\Rules\CantidadSesionesRule;
use App\Modules\InscripcionesAtenciones\Rules\DiasHoraRule;
use App\Modules\InscripcionesAtenciones\Rules\DuracionSesionRule;
use App\Modules\InscripcionesAtenciones\Rules\MontoPaqueteRule;
use App\Modules\InscripcionesAtenciones\Rules\MotivoBajaPaqueteRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteDatosObligatoriosRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteObservacionesRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteStateTransitionRule;
use App\Modules\InscripcionesAtenciones\Rules\PaqueteVigenciaRule;
use App\Modules\InscripcionesAtenciones\Rules\SaldoSesionesRule;
use PHPUnit\Framework\TestCase;

final class PaqueteRulesTest extends TestCase
{
    public function test_cantidad_accepts_positive_integers_and_numeric_strings(): void
    {
        $this->assertSame(1, CantidadSesionesRule::assertValida(1));
        $this->assertSame(10, CantidadSesionesRule::assertValida('10'));
    }

    public function test_cantidad_rejects_zero_negative_and_non_numeric(): void
    {
        foreach ([0, -1, '0', 'abc', 1.5, null] as $invalido) {
            try {
                CantidadSesionesRule::assertValida($invalido);
                $this->fail('Se esperaba CantidadSesionesInvalidaException.');
            } catch (CantidadSesionesInvalidaException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_duracion_rejects_zero_and_negative(): void
    {
        $this->assertSame(50, DuracionSesionRule::assertValida('50'));

        $this->expectException(DuracionSesionInvalidaException::class);
        DuracionSesionRule::assertValida(0);
    }

    public function test_dias_are_sorted_and_serialized_as_csv(): void
    {
        $this->assertSame('1,3,5', DiasHoraRule::assertDiasValidos([5, 1, 3]));
        $this->assertSame('6', DiasHoraRule::assertDiasValidos(['6']));
    }

    public function test_dias_reject_empty_duplicates_and_out_of_range(): void
    {
        foreach ([[], [1, 1], [0], [7], ['x']] as $invalido) {
            try {
                DiasHoraRule::assertDiasValidos($invalido);
                $this->fail('Se esperaba DiasSemanaInvalidosException.');
            } catch (DiasSemanaInvalidosException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_hora_accepts_hh_mm_and_rejects_invalid_values(): void
    {
        $this->assertSame('08:05', DiasHoraRule::assertHoraValida('08:05'));

        foreach (['8:05', '24:00', '12:60', 'abc', ''] as $invalido) {
            try {
                DiasHoraRule::assertHoraValida($invalido);
                $this->fail('Se esperaba HoraInvalidaException.');
            } catch (HoraInvalidaException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_vigencia_accepts_same_day_and_rejects_inverted_or_missing(): void
    {
        PaqueteVigenciaRule::assertValido('2026-11-26', '2026-11-26');
        $this->addToAssertionCount(1);

        foreach ([
            ['2026-11-26', '2026-11-25'],
            ['2026-02-30', '2026-03-01'],
            [null, '2026-03-01'],
            ['2026-03-01', ''],
        ] as [$inicio, $fin]) {
            try {
                PaqueteVigenciaRule::assertValido($inicio, $fin);
                $this->fail('Se esperaba PaquetePeriodoInvalidoException.');
            } catch (PaquetePeriodoInvalidoException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_monto_total_multiplies_exactly(): void
    {
        $this->assertSame('500.00', MontoPaqueteRule::calcular('50.00', 10));
        $this->assertSame('0.00', MontoPaqueteRule::calcular('0.00', 10));
        $this->assertSame('33.33', MontoPaqueteRule::calcular('11.11', 3));
    }

    public function test_saldo_discounts_realized_and_missed_but_not_cancelled(): void
    {
        $this->assertSame(4, SaldoSesionesRule::calcular(10, 4, 2));
        $this->assertSame(10, SaldoSesionesRule::calcular(10, 0, 0));
        $this->assertSame(0, SaldoSesionesRule::calcular(10, 11, 0));
        $this->assertSame(6, SaldoSesionesRule::calcular(10, 4, 0));
    }

    public function test_motivo_requires_detail_for_otro(): void
    {
        MotivoBajaPaqueteRule::assertValido(MotivoBajaPaquete::RETIRO_PACIENTE, null);
        MotivoBajaPaqueteRule::assertValido(MotivoBajaPaquete::OTRO, 'Detalle concreto');
        $this->addToAssertionCount(2);

        $this->expectException(MotivoBajaPaqueteInvalidoException::class);
        MotivoBajaPaqueteRule::assertValido(MotivoBajaPaquete::OTRO, '   ');
    }

    public function test_observaciones_limit_is_500(): void
    {
        PaqueteObservacionesRule::assertValidas(str_repeat('a', 500));
        $this->addToAssertionCount(1);

        $this->expectException(ObservacionesInvalidasException::class);
        PaqueteObservacionesRule::assertValidas(str_repeat('a', 501));
    }

    public function test_required_data_reports_missing_fields(): void
    {
        $this->expectException(PaqueteDatosIncompletosException::class);

        PaqueteDatosObligatoriosRule::assertCompletos([
            'paciente_id' => 1,
            'dias_semana' => [],
        ]);
    }

    public function test_required_data_accepts_a_complete_payload(): void
    {
        PaqueteDatosObligatoriosRule::assertCompletos([
            'paciente_id' => 1,
            'tutor_responsable_id' => 1,
            'sucursal_id' => 1,
            'servicio_id' => 1,
            'tarifa_id' => 1,
            'categoria_tarifa_id' => 1,
            'cantidad_sesiones' => 10,
            'dias_semana' => [1],
            'hora' => '08:00',
            'duracion_minutos' => 50,
            'fecha_inicio' => '2026-11-26',
            'fecha_fin' => '2026-12-25',
        ]);

        $this->addToAssertionCount(1);
    }

    public function test_only_active_packages_can_be_managed(): void
    {
        PaqueteStateTransitionRule::assertPuedeEditar(EstadoPaqueteSesiones::ACTIVO);
        PaqueteStateTransitionRule::assertPuedeRenovar(EstadoPaqueteSesiones::ACTIVO);
        PaqueteStateTransitionRule::assertPuedeRetirar(EstadoPaqueteSesiones::ACTIVO);
        $this->addToAssertionCount(3);

        try {
            PaqueteStateTransitionRule::assertPuedeEditar(EstadoPaqueteSesiones::RETIRADO);
            $this->fail('Se esperaba PaqueteRetiradoException.');
        } catch (PaqueteRetiradoException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(PaqueteNoGestionableException::class);
        PaqueteStateTransitionRule::assertPuedeRenovar(EstadoPaqueteSesiones::EN_PAUSA);
    }

    public function test_estado_mostrado_derives_vencido_without_persisting(): void
    {
        $this->assertSame(
            EstadoPaqueteSesiones::VENCIDO,
            EstadoPaqueteSesiones::ACTIVO->mostrado('2026-11-24', '2026-11-25'),
        );

        $this->assertSame(
            EstadoPaqueteSesiones::ACTIVO,
            EstadoPaqueteSesiones::ACTIVO->mostrado('2026-11-25', '2026-11-25'),
        );

        $this->assertSame(
            EstadoPaqueteSesiones::RETIRADO,
            EstadoPaqueteSesiones::RETIRADO->mostrado('2026-11-24', '2026-11-25'),
        );
    }
}
