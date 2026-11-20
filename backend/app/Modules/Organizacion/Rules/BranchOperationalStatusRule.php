<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\InactiveBranchException;

/**
 * Sucursal inactiva (RN-003).
 *
 * Los módulos consumidores deben llamar a esta regla antes de crear
 * inscripciones, turnos, pagos o aperturas de caja.
 */
final class BranchOperationalStatusRule
{
    public static function assertActive(bool $active, int $branchId, string $branchName): void
    {
        if (! $active) {
            throw new InactiveBranchException($branchId, $branchName);
        }
    }
}
