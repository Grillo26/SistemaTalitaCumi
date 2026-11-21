<?php

namespace App\Modules\Profesionales\Rules;

use App\Modules\Profesionales\Exceptions\StatusReasonRequiredException;
use App\Modules\Profesionales\Exceptions\StatusUnchangedException;

/**
 * Cambio de estado del profesional (03/RF-D07 / 03/RN-20).
 *
 * El motivo es obligatorio en ambos sentidos para conservar la trazabilidad.
 */
final class ProfesionalStatusChangeRule
{
    public function reason(?string $reason, bool $targetStatus): string
    {
        $reason = trim((string) $reason);

        if ($reason === '') {
            throw new StatusReasonRequiredException('profesional');
        }

        return $reason;
    }

    public function assertDifferent(bool $currentStatus, bool $targetStatus): void
    {
        if ($currentStatus === $targetStatus) {
            throw new StatusUnchangedException($targetStatus, 'El profesional');
        }
    }
}
