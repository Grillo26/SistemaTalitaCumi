<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\StatusReasonRequiredException;
use App\Modules\Organizacion\Exceptions\StatusUnchangedException;

/**
 * Cambio de estado de un espacio o catálogo (RN-010).
 *
 * El motivo es obligatorio solo al desactivar.
 */
final class SpaceStatusChangeRule
{
    public function reason(string $reason): string
    {
        $normalized = trim($reason);

        if ($normalized === '') {
            throw new StatusReasonRequiredException;
        }

        return $normalized;
    }

    public function assertDifferent(bool $currentStatus, bool $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw new StatusUnchangedException($targetStatus);
        }
    }
}
