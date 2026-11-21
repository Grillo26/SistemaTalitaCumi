<?php

namespace App\Modules\Seguridad\Support;

use Carbon\CarbonImmutable;

/**
 * Convierte fechas locales (America/La_Paz) a instantes UTC para la bitácora.
 */
final class AuditDateRange
{
    public static function start(?string $date): ?CarbonImmutable
    {
        if ($date === null || $date === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat(
            'Y-m-d',
            $date,
            (string) config('app.timezone'),
        )->startOfDay()->utc();
    }

    public static function end(?string $date): ?CarbonImmutable
    {
        if ($date === null || $date === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat(
            'Y-m-d',
            $date,
            (string) config('app.timezone'),
        )->endOfDay()->utc();
    }
}
