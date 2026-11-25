<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\ConsentimientoDateInvalidException;
use Carbon\CarbonImmutable;

/**
 * Fecha de un consentimiento (04.03 Fase B).
 *
 * Se permite registrar un consentimiento con fecha pasada o de hoy; no se
 * permite el futuro.
 */
final class ConsentimientoDateRule
{
    public function assertNotFuture(CarbonImmutable $fecha, CarbonImmutable $today): void
    {
        if ($fecha->startOfDay()->greaterThan($today->startOfDay())) {
            throw new ConsentimientoDateInvalidException;
        }
    }
}
