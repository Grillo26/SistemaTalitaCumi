<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Estado inicial de una sucursal (RN-002).
 *
 * Toda sucursal nueva nace activa salvo indicación expresa.
 */
final class BranchInitialStatusRule
{
    public static function resolve(?bool $requestedStatus): bool
    {
        return $requestedStatus ?? true;
    }
}
