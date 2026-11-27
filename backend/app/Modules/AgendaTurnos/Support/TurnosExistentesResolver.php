<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Models\Turno;

/**
 * Consultas de turnos para la programación. Se mantienen fuera de las Rules:
 * las Rules reciben los turnos ya materializados.
 */
final class TurnosExistentesResolver
{
    /**
     * Todos los turnos de una entidad origen, en orden cronológico.
     *
     * @return list<TurnoExistente>
     */
    public function delOrigen(TipoOrigenTurno $tipo, int $origenId): array
    {
        $query = Turno::query()->ordenado();

        if ($tipo === TipoOrigenTurno::INSCRIPCION_SALA) {
            $query->where('inscripcion_sala_id', $origenId);
        } else {
            $query->where('paquete_sesiones_id', $origenId);
        }

        return $query->get()
            ->map(static fn (Turno $turno): TurnoExistente => TurnoExistente::fromTurno($turno))
            ->all();
    }

    public function activosDelOrigen(TipoOrigenTurno $tipo, int $origenId): int
    {
        $query = Turno::query()->vigentes();

        if ($tipo === TipoOrigenTurno::INSCRIPCION_SALA) {
            $query->where('inscripcion_sala_id', $origenId);
        } else {
            $query->where('paquete_sesiones_id', $origenId);
        }

        return $query->count();
    }

    /**
     * Turnos que podrían solaparse con el patrón: mismo paciente, mismo
     * profesional o misma sala, dentro del rango de fechas.
     *
     * @return list<TurnoExistente>
     */
    public function conflictivos(
        OrigenProgramacion $origen,
        ?int $profesionalId,
        string $desde,
        string $hasta,
    ): array {
        if ($desde > $hasta) {
            return [];
        }

        return Turno::query()
            ->enRango($desde, $hasta)
            ->vigentes()
            ->where(static function ($query) use ($origen, $profesionalId): void {
                $query->where('paciente_id', $origen->pacienteId);

                if ($profesionalId !== null) {
                    $query->orWhere('profesional_id', $profesionalId);
                }

                if ($origen->salaId !== null) {
                    $query->orWhere('sala_id', $origen->salaId);
                }
            })
            ->ordenado()
            ->get()
            ->map(static fn (Turno $turno): TurnoExistente => TurnoExistente::fromTurno($turno))
            ->all();
    }

    /**
     * Turnos del profesional en el rango, excluyendo los indicados.
     *
     * @param  list<int>  $excluirIds
     * @return list<TurnoExistente>
     */
    public function delProfesional(
        int $profesionalId,
        string $desde,
        string $hasta,
        array $excluirIds = [],
    ): array {
        $query = Turno::query()
            ->enRango($desde, $hasta)
            ->where('profesional_id', $profesionalId)
            ->vigentes();

        if ($excluirIds !== []) {
            $query->whereNotIn('id', $excluirIds);
        }

        return $query->ordenado()
            ->get()
            ->map(static fn (Turno $turno): TurnoExistente => TurnoExistente::fromTurno($turno))
            ->all();
    }

    /**
     * @param  list<int>  $turnoIds
     * @return list<TurnoExistente>
     */
    public function porIds(array $turnoIds): array
    {
        if ($turnoIds === []) {
            return [];
        }

        return Turno::query()
            ->whereIn('id', $turnoIds)
            ->ordenado()
            ->get()
            ->map(static fn (Turno $turno): TurnoExistente => TurnoExistente::fromTurno($turno))
            ->all();
    }

    /**
     * @param  list<int>  $turnoIds
     */
    public function estadosDe(EstadoTurno $estado, array $turnoIds): int
    {
        if ($turnoIds === []) {
            return 0;
        }

        return Turno::query()
            ->whereIn('id', $turnoIds)
            ->where('estado', $estado->value)
            ->count();
    }
}
