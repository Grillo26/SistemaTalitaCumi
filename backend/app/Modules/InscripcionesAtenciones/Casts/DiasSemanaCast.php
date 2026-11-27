<?php

namespace App\Modules\InscripcionesAtenciones\Casts;

use App\Modules\InscripcionesAtenciones\Enums\DiaSemana;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Convierte `dias_semana` entre CSV numérico (`1,3,5`) y arreglo ordenado.
 *
 * @implements CastsAttributes<list<int>, list<int>|string|null>
 */
final class DiasSemanaCast implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return list<int>
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $dias = array_map('intval', explode(',', (string) $value));

        return self::normalize($dias);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        $dias = is_array($value) ? $value : explode(',', (string) $value);

        return implode(',', self::normalize($dias));
    }

    /**
     * @param  array<int, mixed>  $dias
     * @return list<int>
     */
    public static function normalize(array $dias): array
    {
        $valid = DiaSemana::values();

        $normalized = array_values(array_unique(array_map(
            static fn (mixed $dia): int => (int) $dia,
            $dias,
        )));

        $normalized = array_values(array_filter(
            $normalized,
            static fn (int $dia): bool => in_array($dia, $valid, true),
        ));

        sort($normalized);

        return $normalized;
    }
}
