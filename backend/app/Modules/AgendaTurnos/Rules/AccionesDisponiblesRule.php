<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Support\AccionTurnoDefinicion;

/**
 * [Agenda Semanal] RF-009.
 *
 * Filtra las acciones registradas por 7.3/7.4 según estado, permisos y
 * acciones ya ejecutadas. No conoce endpoints ni ejecuta nada.
 */
final class AccionesDisponiblesRule
{
    /**
     * @param  list<string>  $permisos
     * @param  list<AccionTurnoDefinicion>  $acciones
     * @param  list<string>  $accionesRegistradas
     * @return list<AccionTurnoDefinicion>
     */
    public function __invoke(
        EstadoTurno $estado,
        array $permisos,
        array $acciones,
        array $accionesRegistradas = [],
    ): array {
        $disponibles = array_values(array_filter(
            $acciones,
            static function (AccionTurnoDefinicion $accion) use (
                $estado,
                $permisos,
                $accionesRegistradas,
            ): bool {
                if (! in_array($accion->permiso, $permisos, true)) {
                    return false;
                }

                if (! in_array($estado, $accion->estadosPermitidos, true)) {
                    return false;
                }

                if (
                    ! $accion->repetible
                    && in_array($accion->codigo, $accionesRegistradas, true)
                ) {
                    return false;
                }

                return true;
            },
        ));

        usort(
            $disponibles,
            static fn (AccionTurnoDefinicion $a, AccionTurnoDefinicion $b): int => [$a->orden, $a->codigo] <=> [$b->orden, $b->codigo],
        );

        return $disponibles;
    }
}
