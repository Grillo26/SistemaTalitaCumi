<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Enums\DiaSemana;
use Carbon\CarbonImmutable;

/**
 * Advertencia no bloqueante: estima cuántas sesiones caben en el período.
 */
final class PaqueteEstimacionRule
{
    /**
     * @param  array<int, int>  $dias
     */
    public static function estimar(array $dias, string $fechaInicio, string $fechaFin): int
    {
        $diasValidos = array_values(array_intersect(DiaSemana::values(), $dias));

        if ($diasValidos === []) {
            return 0;
        }

        $inicio = CarbonImmutable::parse($fechaInicio)->startOfDay();
        $fin = CarbonImmutable::parse($fechaFin)->startOfDay();
        $total = 0;

        for ($fecha = $inicio; $fecha->lte($fin); $fecha = $fecha->addDay()) {
            if (in_array($fecha->dayOfWeekIso, $diasValidos, true)) {
                $total++;
            }
        }

        return $total;
    }

    public static function advertencia(int $cantidadSesiones, int $sesionesEstimadas): ?string
    {
        if ($sesionesEstimadas > $cantidadSesiones) {
            return sprintf(
                'El período alcanza para %d sesiones, pero el paquete tiene %d. Revisá las fechas o la cantidad.',
                $sesionesEstimadas,
                $cantidadSesiones,
            );
        }

        if ($sesionesEstimadas < $cantidadSesiones) {
            return sprintf(
                'El período solo alcanza para %d sesiones de las %d contratadas. No se bloquea el alta.',
                $sesionesEstimadas,
                $cantidadSesiones,
            );
        }

        return null;
    }
}
