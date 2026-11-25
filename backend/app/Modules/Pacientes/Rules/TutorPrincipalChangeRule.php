<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\TutorPrincipalReasonRequiredException;

/**
 * Motivo al reemplazar un tutor principal (04.02/RF-005, supuesto).
 */
final class TutorPrincipalChangeRule
{
    public function reason(bool $replacingExistingPrincipal, ?string $reason): ?string
    {
        $reason = trim((string) $reason);

        if ($replacingExistingPrincipal && $reason === '') {
            throw new TutorPrincipalReasonRequiredException;
        }

        return $reason === '' ? null : $reason;
    }
}
