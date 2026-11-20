<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Contexto operativo único (RN-009).
 *
 * En esta etapa no hay moneda ni zona horaria por sucursal.
 */
final class BranchOperationalContextRule
{
    public const CURRENCY = 'BOB';

    public const TIMEZONE = 'America/La_Paz';

    public static function matches(string $currency, string $timezone): bool
    {
        return $currency === self::CURRENCY && $timezone === self::TIMEZONE;
    }
}
