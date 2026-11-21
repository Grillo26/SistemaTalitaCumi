<?php

namespace App\Modules\Seguridad\Rules;

use App\Modules\Seguridad\Exceptions\LastSuperadminProtectedException;

/**
 * Protección del último Superadmin activo (02/RF-R08 / 02/RN-16).
 *
 * Regla pura: recibe los hechos ya calculados por la Action.
 */
final class LastSuperadminRule
{
    public function assertCanDeactivate(
        bool $targetIsSuperadmin,
        bool $targetIsActive,
        int $activeSuperadmins,
    ): void {
        if ($targetIsSuperadmin && $targetIsActive && $activeSuperadmins <= 1) {
            throw new LastSuperadminProtectedException(
                'No se puede desactivar al último Superadmin activo.',
            );
        }
    }

    public function assertCanChangeRole(
        bool $targetIsSuperadmin,
        bool $targetIsActive,
        bool $newRoleIsSuperadmin,
        int $activeSuperadmins,
    ): void {
        if ($targetIsSuperadmin
            && $targetIsActive
            && ! $newRoleIsSuperadmin
            && $activeSuperadmins <= 1) {
            throw new LastSuperadminProtectedException(
                'No se puede quitar el rol al último Superadmin activo.',
            );
        }
    }
}
