<?php

namespace App\Modules\Profesionales\Rules;

/**
 * Estado inicial de una especialidad (03/CU-04).
 */
final class EspecialidadInitialStatusRule
{
    public static function resolve(?bool $activo): bool
    {
        return $activo ?? true;
    }
}
