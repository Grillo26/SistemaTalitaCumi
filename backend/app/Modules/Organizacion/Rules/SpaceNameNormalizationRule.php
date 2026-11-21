<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Normalización de nombres de espacios y catálogos.
 */
final class SpaceNameNormalizationRule
{
    public static function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    public static function fold(string $value): string
    {
        return mb_strtolower(self::normalize($value));
    }
}
