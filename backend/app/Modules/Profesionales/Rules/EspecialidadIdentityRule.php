<?php

namespace App\Modules\Profesionales\Rules;

/**
 * Normalización de identidad de la especialidad (03/RF-E01 / 03/RN-06).
 */
final class EspecialidadIdentityRule
{
    public static function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }
}
