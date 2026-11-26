<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use Carbon\CarbonImmutable;

/**
 * Fechas de vigencia con la zona horaria de la aplicación.
 */
final class VigenciaPeriod
{
    public static function today(): string
    {
        return CarbonImmutable::now(config('app.timezone'))->toDateString();
    }

    public static function addDays(string $fecha, int $dias): string
    {
        return CarbonImmutable::parse($fecha)->addDays($dias)->toDateString();
    }
}
