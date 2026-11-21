<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Exceptions\EspecialidadEnUsoException;

/**
 * Bloqueo de desactivación con profesionales activos (03/RF-E02 / 03/RN-09).
 */
final class EspecialidadDeactivationRule
{
    public function assertCanDeactivate(int $activeProfessionals, string $nombre): void
    {
        if ($activeProfessionals > 0) {
            throw new EspecialidadEnUsoException($nombre, $activeProfessionals);
        }
    }
}
