<?php

namespace App\Modules\Organizacion\Rules;

/**
 * Confirmación explícita y motivo para parámetros sensibles (RF-016 / RN-006).
 */
final class SensitiveParameterRule
{
    public static function error(bool $sensible, bool $confirmado, ?string $motivo): ?string
    {
        if (! $sensible) {
            return null;
        }

        if (! $confirmado) {
            return 'Este parámetro es sensible: debés confirmar explícitamente el cambio.';
        }

        if (trim((string) $motivo) === '') {
            return 'Este parámetro es sensible: el motivo del cambio es obligatorio.';
        }

        return null;
    }

    public static function normalizedReason(?string $motivo): ?string
    {
        $motivo = trim((string) $motivo);

        return $motivo === '' ? null : $motivo;
    }
}
