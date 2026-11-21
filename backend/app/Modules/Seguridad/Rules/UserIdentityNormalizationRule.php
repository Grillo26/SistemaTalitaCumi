<?php

namespace App\Modules\Seguridad\Rules;

/**
 * Normalización de identidad de usuario (02/RF-U02 / 02/RN-01).
 *
 * Regla pura: no usa DB, Request ni facades.
 */
final class UserIdentityNormalizationRule
{
    public static function name(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $value));
    }

    public static function email(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public static function emailOrNull(?string $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : self::email($normalized);
    }

    public static function username(string $value): string
    {
        return mb_strtolower(trim($value));
    }

    public static function documento(string $value): string
    {
        return mb_strtoupper(trim($value));
    }
}
