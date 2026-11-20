<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;

/**
 * Coherencia espacio–sucursal–operación (RN-005).
 */
final class SpaceSucursalCoherenceRule
{
    public static function assertSameBranch(
        int $spaceSucursalId,
        int $expectedSucursalId,
        string $entityLabel,
        int $spaceId,
    ): void {
        if ($spaceSucursalId !== $expectedSucursalId) {
            throw new EspacioSucursalIncorrectaException(
                $entityLabel,
                $spaceId,
                $expectedSucursalId,
                $spaceSucursalId,
            );
        }
    }
}
