<?php

namespace App\Modules\CatalogoServicios\Rules;

/**
 * Normalización de nombres de áreas, servicios y tipos (RF-C03).
 *
 * Regla pura: sin BD, Request ni facades.
 */
final class NombreNormalizadoRule
{
    /**
     * Nombre canónico para guardar: sin espacios externos y con espacios
     * internos colapsados. Conserva mayúsculas y acentos.
     */
    public static function normalize(string $value): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $value));
    }

    /**
     * Clave de comparación: sin mayúsculas y con espacios normalizados.
     */
    public static function fold(string $value): string
    {
        return mb_strtolower(self::normalize($value));
    }
}
