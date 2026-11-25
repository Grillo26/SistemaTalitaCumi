<?php

namespace App\Modules\Pacientes\Rules;

/**
 * Normalización de identidad del paciente (04.01/RF-001).
 */
final class PacienteIdentityRule
{
    public static function normalizeName(string $name): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $name));
    }

    public static function normalizeDocument(?string $document): ?string
    {
        $document = trim((string) preg_replace('/\s+/u', ' ', (string) $document));

        return $document === '' ? null : mb_strtoupper($document);
    }

    public static function normalizeOptional(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
