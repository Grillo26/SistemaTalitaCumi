<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Exceptions\StatusReasonRequiredException;
use App\Modules\Profesionales\Exceptions\StatusUnchangedException;

/**
 * Cambio de estado de la especialidad (03/RN-20).
 */
final class EspecialidadStatusChangeRule
{
    public function reason(?string $reason): string
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw new StatusReasonRequiredException('la especialidad');
        }

        return $reason;
    }

    public function assertDifferent(bool $currentStatus, bool $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw new StatusUnchangedException($targetStatus, 'La especialidad');
        }
    }
}
