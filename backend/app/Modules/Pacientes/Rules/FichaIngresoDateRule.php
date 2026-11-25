<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\FichaIngresoDateInvalidException;
use Carbon\CarbonImmutable;

/**
 * Fecha de ingreso válida (04.03/RF-002).
 *
 * No se inventa un rango mínimo: solo se prohíbe el futuro.
 */
final class FichaIngresoDateRule
{
    public function assertNotFuture(CarbonImmutable $fechaIngreso, CarbonImmutable $today): void
    {
        if ($fechaIngreso->startOfDay()->greaterThan($today->startOfDay())) {
            throw new FichaIngresoDateInvalidException;
        }
    }
}
