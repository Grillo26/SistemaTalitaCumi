<?php

namespace App\Modules\Pagos\Rules;

use App\Modules\Pagos\Exceptions\PagoMontoInvalidoException;

/**
 * Monto de una línea de pago (RF-010 / RN-004 / CU-02 FE-01).
 *
 * Dinero como string decimal exacto. Rechaza (no redondea) más de dos
 * decimales, valores no positivos y floats.
 */
final class MontoPagoRule
{
    public const MAXIMO = '9999999999.99';

    public static function normalizar(mixed $monto): string
    {
        if (is_int($monto)) {
            $monto = (string) $monto;
        }

        if (! is_string($monto) || preg_match('/^\d{1,10}(\.\d{1,2})?$/', trim($monto)) !== 1) {
            throw new PagoMontoInvalidoException;
        }

        $normalizado = trim($monto);

        if (! str_contains($normalizado, '.')) {
            $normalizado .= '.00';
        } elseif (strlen(substr($normalizado, strpos($normalizado, '.') + 1)) === 1) {
            $normalizado .= '0';
        }

        if (bccomp($normalizado, '0.00', 2) <= 0 || bccomp($normalizado, self::MAXIMO, 2) > 0) {
            throw new PagoMontoInvalidoException;
        }

        return $normalizado;
    }
}
