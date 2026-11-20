<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Normalización de identidad de sucursal (RN-001 / RF-002).
 *
 * Regla pura: no toca base de datos, Request ni facades.
 */
final class BranchIdentityNormalizationRule
{
    /**
     * Código canónico: sin espacios externos, espacios internos colapsados y
     * en mayúsculas para que la comparación sea estable.
     */
    public static function normalizeCode(string $value): string
    {
        return mb_strtoupper(self::collapseWhitespace($value));
    }

    /**
     * Nombre canónico: sin espacios externos y con espacios internos colapsados.
     * La comparación de unicidad se hace con fold().
     */
    public static function normalizeName(string $value): string
    {
        return self::collapseWhitespace($value);
    }

    /**
     * Representación para comparar sin distinguir mayúsculas ni espacios.
     */
    public static function fold(string $value): string
    {
        return mb_strtolower(self::collapseWhitespace($value));
    }

    private static function collapseWhitespace(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }
}
