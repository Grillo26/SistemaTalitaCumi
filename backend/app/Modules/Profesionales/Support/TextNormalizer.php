<?php

namespace App\Modules\Profesionales\Support;

/**
 * Clave de comparación textual independiente de mayúsculas y espacios externos.
 *
 * Se usa en las reglas de unicidad porque SQLite no aplica `lower()` a
 * caracteres acentuados; PostgreSQL sí. Comparar en PHP mantiene el mismo
 * comportamiento en ambos motores.
 */
final class TextNormalizer
{
    public static function key(?string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        return mb_strtolower($value);
    }
}
