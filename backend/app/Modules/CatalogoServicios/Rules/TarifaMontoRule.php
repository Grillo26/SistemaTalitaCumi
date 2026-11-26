<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TarifaMontoInvalidoException;

/**
 * Monto de tarifa en Bs (RN-T01 / RF-T06).
 *
 * Trabaja siempre con strings: no convierte a float.
 */
final class TarifaMontoRule
{
    private const PATTERN = '/^\d{1,10}(\.\d{1,2})?$/';

    public static function normalizeAndValidate(?string $monto): string
    {
        $value = trim((string) $monto);

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new TarifaMontoInvalidoException;
        }

        [$integer, $decimal] = array_pad(explode('.', $value, 2), 2, '');
        $integer = ltrim($integer, '0');
        $integer = $integer === '' ? '0' : $integer;

        return $integer.'.'.str_pad($decimal, 2, '0');
    }
}
