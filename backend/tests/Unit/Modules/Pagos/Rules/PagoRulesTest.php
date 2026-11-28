<?php

namespace Tests\Unit\Modules\Pagos\Rules;

use App\Modules\Pagos\Exceptions\PagoFechaFuturaNoAutorizadaException;
use App\Modules\Pagos\Exceptions\PagoFechaInvalidaException;
use App\Modules\Pagos\Exceptions\PagoLineasInvalidasException;
use App\Modules\Pagos\Exceptions\PagoMontoInvalidoException;
use App\Modules\Pagos\Exceptions\PagoSaldoExcedidoException;
use App\Modules\Pagos\Rules\FechaPagoRule;
use App\Modules\Pagos\Rules\MontoPagoRule;
use App\Modules\Pagos\Rules\PagoIdempotenciaRule;
use App\Modules\Pagos\Rules\PagoLineasRule;
use App\Modules\Pagos\Rules\SaldoPagoRule;
use App\Modules\Pagos\Support\PagoNumberFormatter;
use PHPUnit\Framework\TestCase;

final class PagoRulesTest extends TestCase
{
    public function test_monto_accepts_exact_decimals_and_normalizes(): void
    {
        $this->assertSame('500.00', MontoPagoRule::normalizar('500'));
        $this->assertSame('500.50', MontoPagoRule::normalizar('500.5'));
        $this->assertSame('500.00', MontoPagoRule::normalizar('500.00'));
        $this->assertSame('200.00', MontoPagoRule::normalizar(200));
    }

    public function test_monto_rejects_zero_negative_extra_decimals_floats_and_text(): void
    {
        foreach (['0', '0.00', '-1', '1.234', 'abc', '', '10000000000', 12.5] as $invalido) {
            try {
                MontoPagoRule::normalizar($invalido);
                $this->fail('Se aceptó un monto inválido: '.var_export($invalido, true));
            } catch (PagoMontoInvalidoException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_saldo_counts_only_what_is_passed_as_vigente_and_never_goes_negative(): void
    {
        $this->assertSame('300.00', SaldoPagoRule::saldo('500.00', '200.00'));
        $this->assertSame('0.00', SaldoPagoRule::saldo('500.00', '500.00'));
        $this->assertSame('0.00', SaldoPagoRule::saldo('500.00', '600.00'));
    }

    public function test_total_above_saldo_is_rejected_with_maximum(): void
    {
        SaldoPagoRule::assertCubre('300.00', '300.00');
        $this->addToAssertionCount(1);

        try {
            SaldoPagoRule::assertCubre('300.01', '300.00');
            $this->fail('El total excedió el saldo sin error.');
        } catch (PagoSaldoExcedidoException $exception) {
            $this->assertSame('300.00', $exception->saldoMaximo);
        }
    }

    public function test_past_and_today_dates_are_allowed_without_authorization(): void
    {
        $this->assertFalse(FechaPagoRule::assertValida('2026-11-20', '2026-11-25', false, null));
        $this->assertFalse(FechaPagoRule::assertValida('2026-11-25', '2026-11-25', false, null));
    }

    public function test_future_date_requires_authorization_and_reason(): void
    {
        try {
            FechaPagoRule::assertValida('2026-11-26', '2026-11-25', false, 'Motivo');
            $this->fail('Fecha futura sin permiso aceptada.');
        } catch (PagoFechaFuturaNoAutorizadaException) {
            $this->addToAssertionCount(1);
        }

        try {
            FechaPagoRule::assertValida('2026-11-26', '2026-11-25', true, '   ');
            $this->fail('Fecha futura sin motivo aceptada.');
        } catch (PagoFechaInvalidaException) {
            $this->addToAssertionCount(1);
        }

        $this->assertTrue(FechaPagoRule::assertValida('2026-11-26', '2026-11-25', true, 'Cobro adelantado'));
    }

    public function test_invalid_date_format_is_rejected(): void
    {
        $this->expectException(PagoFechaInvalidaException::class);
        FechaPagoRule::assertValida('25/11/2026', '2026-11-25', true, null);
    }

    public function test_lines_require_one_or_two_distinct_active_mediums(): void
    {
        PagoLineasRule::assertValidas([['medio_pago' => 'efectivo', 'referencia' => null]], ['efectivo', 'qr']);
        PagoLineasRule::assertValidas([
            ['medio_pago' => 'efectivo', 'referencia' => null],
            ['medio_pago' => 'qr', 'referencia' => 'REF-778'],
        ], ['efectivo', 'qr']);
        $this->addToAssertionCount(2);

        $casos = [
            [],
            [
                ['medio_pago' => 'efectivo', 'referencia' => null],
                ['medio_pago' => 'efectivo', 'referencia' => null],
            ],
            [['medio_pago' => 'tarjeta', 'referencia' => null]],
            [
                ['medio_pago' => 'efectivo', 'referencia' => null],
                ['medio_pago' => 'qr', 'referencia' => null],
                ['medio_pago' => 'qr', 'referencia' => null],
            ],
        ];

        foreach ($casos as $lineas) {
            try {
                PagoLineasRule::assertValidas($lineas, ['efectivo', 'qr']);
                $this->fail('Líneas inválidas aceptadas.');
            } catch (PagoLineasInvalidasException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_qr_requires_bank_reference(): void
    {
        $this->expectException(PagoLineasInvalidasException::class);

        PagoLineasRule::assertValidas([['medio_pago' => 'qr', 'referencia' => '  ']], ['efectivo', 'qr']);
    }

    public function test_idempotency_fingerprint_ignores_key_order_and_detects_changes(): void
    {
        $a = PagoIdempotenciaRule::huella(['monto' => '200.00', 'medio' => 'qr']);
        $b = PagoIdempotenciaRule::huella(['medio' => 'qr', 'monto' => '200.00']);
        $c = PagoIdempotenciaRule::huella(['monto' => '201.00', 'medio' => 'qr']);

        $this->assertSame($a, $b);
        $this->assertNotSame($a, $c);
    }

    public function test_receipt_number_uses_configured_prefix_and_format(): void
    {
        $this->assertSame('TC-000001', PagoNumberFormatter::formatear('TC', '{prefijo}-{numero:6}', 1));
        $this->assertSame('TC-000123', PagoNumberFormatter::formatear('TC', '{prefijo}-{numero:6}', 123));
        $this->assertSame('REC-7', PagoNumberFormatter::formatear('REC', '{prefijo}-{numero}', 7));
    }
}
