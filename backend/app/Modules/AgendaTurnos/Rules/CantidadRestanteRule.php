<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Support\TurnoExistente;

/**
 * [Programación de Turnos] RF-010 / RN-006 / CA-007.
 *
 * Sesiones que aún puede generar un paquete al regenerar:
 * cantidad pactada menos Asistió y Faltó. Las Canceladas no consumen.
 */
final class CantidadRestanteRule
{
    /**
     * @param  list<TurnoExistente>  $turnos
     */
    public function calcular(int $cantidadPactada, array $turnos): int
    {
        $consumidas = 0;

        foreach ($turnos as $turno) {
            if ($turno->estado === EstadoTurno::ASISTIO || $turno->estado === EstadoTurno::FALTO) {
                $consumidas++;

                continue;
            }

            if ($turno->estado === EstadoTurno::CANCELADO && $turno->consumeSaldo) {
                $consumidas++;
            }
        }

        return max(0, $cantidadPactada - $consumidas);
    }

    public function faltantes(int $objetivo, int $generables): int
    {
        return max(0, $objetivo - $generables);
    }
}
