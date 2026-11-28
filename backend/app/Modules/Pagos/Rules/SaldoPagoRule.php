<?php

namespace App\Modules\Pagos\Rules;

use App\Modules\Pagos\Exceptions\PagoSaldoExcedidoException;

/**
 * Saldo pendiente (08.01/RF-004, RN-004, RN-011).
 *
 * saldo = monto esperado − suma de pagos VIGENTES. Los anulados no cuentan.
 */
final class SaldoPagoRule
{
    public static function saldo(string $montoEsperado, string $pagadoVigente): string
    {
        $saldo = bcsub($montoEsperado, $pagadoVigente, 2);

        return bccomp($saldo, '0.00', 2) < 0 ? '0.00' : $saldo;
    }

    public static function assertCubre(string $totalSolicitado, string $saldo): void
    {
        if (bccomp($totalSolicitado, $saldo, 2) > 0) {
            throw new PagoSaldoExcedidoException($saldo);
        }
    }

    public static function resultante(string $saldo, string $totalSolicitado): string
    {
        return bcsub($saldo, $totalSolicitado, 2);
    }
}
