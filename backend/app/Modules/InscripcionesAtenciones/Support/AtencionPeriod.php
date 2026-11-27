<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use Carbon\CarbonImmutable;

/**
 * Cálculos de fecha del submódulo de atenciones.
 */
final class AtencionPeriod
{
    public static function today(): string
    {
        return CarbonImmutable::now(config('app.timezone'))->toDateString();
    }
}
