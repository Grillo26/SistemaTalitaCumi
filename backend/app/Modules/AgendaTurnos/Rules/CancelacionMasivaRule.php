<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Support\ClasificacionCancelacionMasiva;
use App\Modules\AgendaTurnos\Support\TurnoExcluido;
use App\Modules\AgendaTurnos\Support\TurnoExistente;

/**
 * [Reprogramación y Cancelación] RF-011 / RN-009 / CA-007 / CU-03 FE-01.
 *
 * Solo entran turnos Programados futuros. Asistió/Faltó futuros se excluyen
 * y se informan; pasados, cancelados y reprogramados no se tocan.
 */
final class CancelacionMasivaRule
{
    public function __construct(private readonly RegeneracionRule $futuro) {}

    /**
     * @param  list<TurnoExistente>  $turnos
     */
    public function evaluar(array $turnos, string $hoy, string $horaActual): ClasificacionCancelacionMasiva
    {
        $elegibles = [];
        $excluidos = [];

        foreach ($turnos as $turno) {
            if ($turno->estado !== EstadoTurno::PROGRAMADO) {
                $excluidos[] = $this->excluido($turno, match ($turno->estado) {
                    EstadoTurno::ASISTIO, EstadoTurno::FALTO => 'El turno ya tiene asistencia registrada.',
                    EstadoTurno::CANCELADO => 'El turno ya está cancelado.',
                    EstadoTurno::REPROGRAMADO => 'El turno fue reprogramado; gestioná el turno vigente.',
                    default => 'El turno no está en estado Programado.',
                });

                continue;
            }

            if (! $this->futuro->esFuturo($turno, $hoy, $horaActual)) {
                $excluidos[] = $this->excluido($turno, 'El turno es pasado; 7.4 debe registrar su asistencia.');

                continue;
            }

            $elegibles[] = $turno;
        }

        return new ClasificacionCancelacionMasiva($elegibles, $excluidos);
    }

    private function excluido(TurnoExistente $turno, string $motivo): TurnoExcluido
    {
        return new TurnoExcluido(
            turnoId: $turno->id,
            motivo: $motivo,
            estado: $turno->estado,
            fecha: $turno->fecha,
            horaInicio: $turno->horaInicio,
        );
    }
}
