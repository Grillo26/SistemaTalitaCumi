<?php

namespace App\Modules\Pacientes\Rules;

use App\Modules\Pacientes\Exceptions\TutorStatusReasonRequiredException;
use App\Modules\Pacientes\Exceptions\TutorStatusUnchangedException;

/**
 * Cambio de estado del tutor (04.02/RF-010).
 *
 * Motivo obligatorio al desactivar. Al activar es opcional.
 */
final class TutorStatusChangeRule
{
    public function reason(bool $targetActive, ?string $reason): ?string
    {
        $reason = trim((string) $reason);

        if (! $targetActive && $reason === '') {
            throw new TutorStatusReasonRequiredException;
        }

        return $reason === '' ? null : $reason;
    }

    public function assertDifferent(bool $currentActive, bool $targetActive): void
    {
        if ($currentActive === $targetActive) {
            throw new TutorStatusUnchangedException($targetActive);
        }
    }
}
