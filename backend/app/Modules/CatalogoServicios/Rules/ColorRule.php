<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\ColorInvalidoException;

/**
 * Validación y normalización del color de área (RF-C04 / RT-07).
 *
 * Acepta #RGB o #RRGGBB. No valida contraste: el color es un dato visual.
 */
final class ColorRule
{
    private const PATTERN = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    public static function normalizeAndValidate(?string $color): string
    {
        $value = mb_strtoupper(trim((string) $color));

        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new ColorInvalidoException;
        }

        return $value;
    }

    public static function isValid(?string $color): bool
    {
        return preg_match(self::PATTERN, mb_strtoupper(trim((string) $color))) === 1;
    }
}
