<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Estado inicial de un espacio o catálogo.
 *
 * Activo por defecto; se permite inactivo explícito al crear.
 */
final class SpaceInitialStatusRule
{
    public static function resolve(?bool $requestedStatus): bool
    {
        return $requestedStatus ?? true;
    }
}
