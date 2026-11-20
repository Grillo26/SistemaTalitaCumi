<?php

namespace App\Modules\Organizacion\Rules;

use App\Modules\Organizacion\Enums\ParameterType;
use App\Modules\Organizacion\Support\ParameterValueCaster;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validación pura de rango (RF-004 / CA-002).
 *
 * Para textos, `minimo` y `maximo` representan longitud en caracteres.
 */
final class ParameterRangeRule implements ValidationRule
{
    public function __construct(
        private readonly ParameterType $type,
        private readonly ?string $min,
        private readonly ?string $max,
        private readonly ?string $unidad = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $error = self::error($this->type, $value, $this->min, $this->max, $this->unidad);

        if ($error !== null) {
            $fail($error);
        }
    }

    /**
     * Comprobación pura reutilizable por la Action (defensa en backend).
     */
    public static function error(
        ParameterType $type,
        mixed $value,
        ?string $min,
        ?string $max,
        ?string $unidad = null,
    ): ?string {
        if ($min === null && $max === null) {
            return null;
        }

        if (! ParameterValueCaster::isValid($type, $value)) {
            return null;
        }

        $normalized = ParameterValueCaster::normalize($type, $value);

        if ($type === ParameterType::TEXTO) {
            $length = mb_strlen($normalized);

            if ($min !== null && is_numeric($min) && $length < (int) $min) {
                return "El valor debe tener al menos {$min} caracteres.";
            }

            if ($max !== null && is_numeric($max) && $length > (int) $max) {
                return "El valor no puede superar los {$max} caracteres.";
            }

            return null;
        }

        $suffix = $unidad !== null && $unidad !== '' ? ' '.$unidad : '';

        if ($min !== null && self::compare($type, $normalized, $min) < 0) {
            return $max !== null
                ? "El valor debe estar entre {$min} y {$max}{$suffix}."
                : "El valor no puede ser menor que {$min}{$suffix}.";
        }

        if ($max !== null && self::compare($type, $normalized, $max) > 0) {
            return $min !== null
                ? "El valor debe estar entre {$min} y {$max}{$suffix}."
                : "El valor no puede ser mayor que {$max}{$suffix}.";
        }

        return null;
    }

    private static function compare(ParameterType $type, string $value, string $limit): int
    {
        if ($type === ParameterType::DECIMAL) {
            return self::compareDecimal($value, trim($limit));
        }

        if ($type === ParameterType::ENTERO) {
            return (int) $value <=> (int) $limit;
        }

        return strcmp($value, trim($limit));
    }

    private static function compareDecimal(string $a, string $b): int
    {
        $a = trim($a);
        $b = trim($b);

        $aNegative = str_starts_with($a, '-');
        $bNegative = str_starts_with($b, '-');

        if ($aNegative !== $bNegative) {
            return $aNegative ? -1 : 1;
        }

        $comparison = self::compareAbsolute(ltrim($a, '-'), ltrim($b, '-'));

        return $aNegative ? -$comparison : $comparison;
    }

    private static function compareAbsolute(string $a, string $b): int
    {
        [$aInteger, $aDecimals] = array_pad(explode('.', $a, 2), 2, '');
        [$bInteger, $bDecimals] = array_pad(explode('.', $b, 2), 2, '');

        $aInteger = ltrim($aInteger, '0') ?: '0';
        $bInteger = ltrim($bInteger, '0') ?: '0';

        if (strlen($aInteger) !== strlen($bInteger)) {
            return strlen($aInteger) <=> strlen($bInteger);
        }

        if ($aInteger !== $bInteger) {
            return $aInteger <=> $bInteger;
        }

        $length = max(strlen($aDecimals), strlen($bDecimals));

        return str_pad($aDecimals, $length, '0') <=> str_pad($bDecimals, $length, '0');
    }
}
