<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Feriado;

/**
 * Feriados activos aplicables a una sucursal y rango de fechas.
 */
final class FeriadosResolver
{
    /**
     * @return array<string, string> fecha (Y-m-d) => descripción
     */
    public function mapa(int $sucursalId, string $desde, string $hasta): array
    {
        if ($desde > $hasta) {
            return [];
        }

        return Feriado::query()
            ->activos()
            ->paraSucursal($sucursalId)
            ->enRango($desde, $hasta)
            ->orderBy('fecha')
            ->get(['fecha', 'descripcion'])
            ->mapWithKeys(static fn (Feriado $feriado): array => [
                $feriado->fecha->toDateString() => (string) $feriado->descripcion,
            ])
            ->all();
    }
}
