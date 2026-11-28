<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Support\ClasificacionRegeneracion;
use App\Modules\AgendaTurnos\Support\TurnoExistente;

/**
 * [Programación de Turnos] RF-010 / RF-011 / RN-006 / RT-004 / CA-007.
 *
 * Al regenerar:
 * - se cancelan solo los turnos futuros en estado Programado;
 * - Asistió, Faltó, Cancelado y Reprogramado se preservan;
 * - los Programados de fechas pasadas se preservan y se informan.
 *
 * "Futuro" se define con la fecha y hora inyectadas en La Paz: fecha > hoy,
 * o fecha = hoy con hora de inicio posterior a la hora actual.
 */
final class RegeneracionRule
{
    /**
     * @param  list<TurnoExistente>  $turnos
     */
    public function evaluar(array $turnos, string $hoy, string $horaActual): ClasificacionRegeneracion
    {
        $cancelar = [];
        $preservar = [];
        $asistidos = [];
        $faltados = [];
        $pasadosSinMarcar = [];

        foreach ($turnos as $turno) {
            if ($turno->estado === EstadoTurno::ASISTIO) {
                $asistidos[] = $turno;
                $preservar[] = $turno;

                continue;
            }

            if ($turno->estado === EstadoTurno::FALTO) {
                $faltados[] = $turno;
                $preservar[] = $turno;

                continue;
            }

            if ($turno->estado !== EstadoTurno::PROGRAMADO) {
                $preservar[] = $turno;

                continue;
            }

            if ($this->esFuturo($turno, $hoy, $horaActual)) {
                $cancelar[] = $turno;

                continue;
            }

            $pasadosSinMarcar[] = $turno;
            $preservar[] = $turno;
        }

        return new ClasificacionRegeneracion(
            cancelar: $cancelar,
            preservar: $preservar,
            asistidos: $asistidos,
            faltados: $faltados,
            pasadosSinMarcar: $pasadosSinMarcar,
        );
    }

    public function esFuturo(TurnoExistente $turno, string $hoy, string $horaActual): bool
    {
        if ($turno->fecha > $hoy) {
            return true;
        }

        if ($turno->fecha < $hoy) {
            return false;
        }

        return $turno->horaInicio > $horaActual;
    }
}
