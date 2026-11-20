<?php

namespace App\Modules\Organizacion\Support;

use App\Modules\Organizacion\Enums\ParameterType;
use InvalidArgumentException;

/**
 * Conversión pura entre el valor serializado y el valor tipado (RF-004).
 *
 * Los decimales se conservan como string para no perder precisión: este
 * módulo no opera con float. Los valores se guardan como texto en la BD.
 */
final class ParameterValueCaster
{
    public static function isValid(ParameterType $type, mixed $value): bool
    {
        return match ($type) {
            ParameterType::ENTERO => is_int($value)
                || (is_string($value) && preg_match('/^-?\d+$/', trim($value)) === 1),
            ParameterType::DECIMAL => is_int($value)
                || is_float($value)
                || (is_string($value) && preg_match('/^-?\d+(\.\d+)?$/', trim($value)) === 1),
            ParameterType::HORA => is_string($value)
                && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', trim($value)) === 1,
            ParameterType::TEXTO => is_string($value) || is_int($value) || is_float($value),
            ParameterType::BOOLEANO => is_bool($value)
                || in_array($value, [0, 1, '0', '1'], true)
                || (is_string($value) && in_array(mb_strtolower(trim($value)), ['true', 'false'], true)),
        };
    }

    /**
     * @throws InvalidArgumentException
     */
    public static function normalize(ParameterType $type, mixed $value): string
    {
        if (! self::isValid($type, $value)) {
            throw new InvalidArgumentException("El valor no corresponde al tipo {$type->label()}.");
        }

        return match ($type) {
            ParameterType::ENTERO => (string) (int) trim((string) $value),
            ParameterType::DECIMAL => self::normalizeDecimal((string) $value),
            ParameterType::HORA => substr(trim((string) $value), 0, 5),
            ParameterType::TEXTO => trim((string) $value),
            ParameterType::BOOLEANO => self::normalizeBoolean($value),
        };
    }

    /**
     * Valor tipado expuesto por el contrato de lectura.
     *
     * Los decimales se devuelven como string para preservar la precisión.
     */
    public static function cast(ParameterType $type, string $value): mixed
    {
        return match ($type) {
            ParameterType::ENTERO => (int) $value,
            ParameterType::DECIMAL => $value,
            ParameterType::HORA => $value,
            ParameterType::TEXTO => $value,
            ParameterType::BOOLEANO => $value === '1',
        };
    }

    private static function normalizeDecimal(string $value): string
    {
        $value = trim($value);

        if (str_contains($value, '.')) {
            [$integer, $decimals] = explode('.', $value, 2);
            $decimals = rtrim($decimals, '0');

            return $decimals === '' ? $integer : $integer.'.'.$decimals;
        }

        return $value;
    }

    private static function normalizeBoolean(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return $value === 1 ? '1' : '0';
        }

        return in_array(mb_strtolower(trim((string) $value)), ['1', 'true'], true) ? '1' : '0';
    }
}
