<?php

namespace App\Modules\AgendaTurnos\Rules;

/**
 * [Agenda Semanal] RF-004, RN-003.
 */
final class TurnoSinAsignarRule
{
    public function __invoke(?int $profesionalId): bool
    {
        return $profesionalId === null;
    }
}
