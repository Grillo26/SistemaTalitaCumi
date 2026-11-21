<?php

namespace App\Modules\Pacientes\Rules;

/**
 * Normalización de identidad del tutor (04.02/RF-001, 04.02/RN-002).
 */
final class TutorIdentityRule
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

    public static function normalizePhone(string $phone): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $phone));
    }

    public static function normalizeOptional(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
