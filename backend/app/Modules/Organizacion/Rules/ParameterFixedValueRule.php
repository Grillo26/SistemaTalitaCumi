<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Valores fijos de la Etapa 1 (RN-003 / RN-010).
 *
 * La moneda es única (boliviano) y la leyenda del comprobante no puede
 * desactivarse ni modificarse desde la interfaz.
 */
final class ParameterFixedValueRule
{
    public const MONEDA_CLAVE = 'moneda';

    public const MONEDA_VALOR = 'BOB';

    public const LEYENDA_CLAVE = 'comprobante.leyenda_no_factura';

    public const LEYENDA_VALOR = 'Este documento NO es factura';

    public static function isFixed(string $clave): bool
    {
        return in_array($clave, [self::MONEDA_CLAVE, self::LEYENDA_CLAVE], true);
    }

    public static function expected(string $clave): ?string
    {
        return match ($clave) {
            self::MONEDA_CLAVE => self::MONEDA_VALOR,
            self::LEYENDA_CLAVE => self::LEYENDA_VALOR,
            default => null,
        };
    }

    public static function matches(string $clave, string $valor): bool
    {
        $expected = self::expected($clave);

        return $expected === null || $expected === $valor;
    }
}
