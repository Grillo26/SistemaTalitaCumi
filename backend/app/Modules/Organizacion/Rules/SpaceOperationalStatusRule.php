<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\EspacioInactivoException;

/**
 * Un espacio inactivo no admite nuevas operaciones (RN-006).
 */
final class SpaceOperationalStatusRule
{
    public static function assertAvailable(
        bool $active,
        string $entityLabel,
        int $id,
        string $name,
    ): void {
        if (! $active) {
            throw new EspacioInactivoException($entityLabel, $id, $name);
        }
    }
}
