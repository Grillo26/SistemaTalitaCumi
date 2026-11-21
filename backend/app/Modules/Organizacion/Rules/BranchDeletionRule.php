<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Exceptions\BranchDeletionNotAllowedException;

/**
 * No eliminación física de sucursales (RN-004 / RF-008 / CA-007).
 *
 * Mientras los módulos operativos no existan, la política es no ofrecer
 * borrado físico: solo desactivar.
 */
final class BranchDeletionRule
{
    public static function reject(string $branchName): never
    {
        throw new BranchDeletionNotAllowedException($branchName);
    }
}
