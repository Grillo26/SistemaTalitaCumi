<?php

namespace App\Modules\Profesionales\Rules;

/**
 * Normalización de identidad del profesional (03/RF-D01, 03/RN-01).
 */
final class ProfesionalIdentityRule
{
    public static function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    public static function normalizeCi(string $ci): string
    {
        return mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $ci)));
    }
}
