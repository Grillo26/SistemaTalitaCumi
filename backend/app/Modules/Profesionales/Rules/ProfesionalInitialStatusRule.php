<?php

namespace App\Modules\Profesionales\Rules;

/**
 * Estado inicial de un profesional (03/CU-01, FA-01).
 */
final class ProfesionalInitialStatusRule
{
    public static function resolve(?bool $activo): bool
    {
        return $activo ?? true;
    }
}
