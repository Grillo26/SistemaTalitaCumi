<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\PacienteInvalidBirthDateException;
use Carbon\CarbonImmutable;

/**
 * Fecha de nacimiento válida (04.01/RF-001).
 *
 * Solo se prohíbe el futuro. No se inventa un rango máximo/mínimo de edad.
 */
final class PacienteBirthDateRule
{
    public function assertNotFuture(CarbonImmutable $birthDate, CarbonImmutable $today): void
    {
        if ($birthDate->startOfDay()->greaterThan($today->startOfDay())) {
            throw new PacienteInvalidBirthDateException;
        }
    }
}
