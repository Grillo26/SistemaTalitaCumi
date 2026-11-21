<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\TutorInactiveException;
use App\Modules\Pacientes\Exceptions\TutorPrincipalRequiredException;

/**
 * Tutor principal (04.02/RF-005 / 04.02/RN-003).
 */
final class TutorPrincipalRule
{
    public function assertCanBePrincipal(bool $tutorActive): void
    {
        if (! $tutorActive) {
            throw new TutorInactiveException;
        }
    }

    /**
     * Un paciente activo no puede quedar sin tutor principal. Para quitar al
     * principal hay que designar otro primero.
     */
    public function assertCanDissociate(bool $isPrincipal, bool $pacienteActivo): void
    {
        if ($isPrincipal && $pacienteActivo) {
            throw new TutorPrincipalRequiredException;
        }
    }
}
