<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\PacienteTutorRequiredException;

/**
 * Tutor obligatorio para activar (04.01/RN-003, 04.01/RF-004).
 */
final class PacienteTutorRequirementRule
{
    public function assertCanActivate(bool $hasPrincipalTutor): void
    {
        if (! $hasPrincipalTutor) {
            throw new PacienteTutorRequiredException;
        }
    }
}
