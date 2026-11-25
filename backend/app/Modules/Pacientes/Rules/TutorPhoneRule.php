<?php

namespace App\Modules\Pacientes\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validación y normalización de teléfonos de tutores (04.02/RN-008).
 *
 * Formato propuesto a validar con el cliente:
 * - Principal: 8 dígitos, móvil boliviano que empieza en 6 o 7.
 * - Alternativo: 8 dígitos, fijo (2/3/4) o móvil (6/7).
 * - Se acepta y normaliza el prefijo país 591 y un 0 inicial.
 */
final class TutorPhoneRule implements ValidationRule
{
    public const PRINCIPAL = 'principal';

    public const ALTERNATE = 'alternate';

    public function __construct(private readonly string $mode = self::PRINCIPAL) {}

    public static function normalize(?string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

        if ($digits === '') {
            return null;
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '591')) {
            $digits = substr($digits, 3);
        }

        if (strlen($digits) === 9 && str_starts_with($digits, '0')) {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    public static function isValidPrincipal(?string $phone): bool
    {
        $phone = self::normalize($phone);

        return $phone !== null
            && strlen($phone) === 8
            && in_array($phone[0], ['6', '7'], true);
    }

    public static function isValidAlternate(?string $phone): bool
    {
        $phone = self::normalize($phone);

        return $phone !== null
            && strlen($phone) === 8
            && in_array($phone[0], ['2', '3', '4', '6', '7'], true);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $valid = $this->mode === self::ALTERNATE
            ? self::isValidAlternate((string) $value)
            : self::isValidPrincipal((string) $value);

        if (! $valid) {
            $fail($this->mode === self::ALTERNATE
                ? 'El teléfono alternativo debe tener 8 dígitos y comenzar con 2, 3, 4, 6 o 7 (formato Bolivia, propuesto a validar).'
                : 'El teléfono principal debe tener 8 dígitos y comenzar con 6 o 7 (formato móvil Bolivia, propuesto a validar).');
        }
    }
}
