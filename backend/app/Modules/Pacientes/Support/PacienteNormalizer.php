<?php

namespace App\Modules\Pacientes\Support;

/**
 * Normalización textual del módulo 04.
 *
 * Se hace en PHP para que SQLite (tests) y PostgreSQL (producción) se
 * comporten igual, sin depender de `unaccent` ni `pg_trgm`.
 */
final class PacienteNormalizer
{
    private const ACCENTS = [
        'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a', 'ã' => 'a',
        'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
        'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o', 'õ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
        'ñ' => 'n', 'ç' => 'c',
        'Á' => 'a', 'À' => 'a', 'Ä' => 'a', 'Â' => 'a', 'Ã' => 'a',
        'É' => 'e', 'È' => 'e', 'Ë' => 'e', 'Ê' => 'e',
        'Í' => 'i', 'Ì' => 'i', 'Ï' => 'i', 'Î' => 'i',
        'Ó' => 'o', 'Ò' => 'o', 'Ö' => 'o', 'Ô' => 'o', 'Õ' => 'o',
        'Ú' => 'u', 'Ù' => 'u', 'Ü' => 'u', 'Û' => 'u',
        'Ñ' => 'n', 'Ç' => 'c',
    ];

    public static function key(?string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', (string) $value));

        if ($value === '') {
            return '';
        }

        $value = strtr($value, self::ACCENTS);

        return mb_strtolower($value);
    }

    public static function documentKey(?string $value): ?string
    {
        $value = self::key($value);

        return $value === '' ? null : $value;
    }

    public static function phoneKey(?string $value): string
    {
        return (string) preg_replace('/\D+/', '', (string) $value);
    }
}
