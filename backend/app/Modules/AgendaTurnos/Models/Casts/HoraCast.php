<?php

namespace App\Modules\AgendaTurnos\Models\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * Normaliza las columnas TIME a HH:MM.
 *
 * @implements CastsAttributes<string|null, string|null>
 */
final class HoraCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        return substr($value, 0, 5);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value) !== 1) {
            throw new InvalidArgumentException("La hora «{$value}» no tiene formato HH:MM.");
        }

        return strlen($value) === 5 ? $value.':00' : $value;
    }
}
