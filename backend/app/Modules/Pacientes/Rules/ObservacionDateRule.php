<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\ObservacionDateInvalidException;
use Carbon\CarbonImmutable;

/**
 * Fecha de una observación fechada (04.03 Fase C, RF-006).
 */
final class ObservacionDateRule
{
    public function assertNotFuture(CarbonImmutable $fecha, CarbonImmutable $today): void
    {
        if ($fecha->startOfDay()->greaterThan($today->startOfDay())) {
            throw new ObservacionDateInvalidException;
        }
    }
}
