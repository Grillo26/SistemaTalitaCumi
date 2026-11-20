<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\BranchStatusReasonRequiredException;
use App\Modules\Organizacion\Exceptions\BranchStatusUnchangedException;

/**
 * Reglas del cambio de estado de sucursal (RN-005 / RF-007).
 *
 * El motivo es obligatorio en ambos sentidos: activa → inactiva e
 * inactiva → activa.
 */
final class BranchStatusChangeRule
{
    public function reason(string $reason): string
    {
        $normalized = trim($reason);

        if ($normalized === '') {
            throw new BranchStatusReasonRequiredException;
        }

        return $normalized;
    }

    public function assertDifferent(bool $currentStatus, bool $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw new BranchStatusUnchangedException($targetStatus);
        }
    }
}
