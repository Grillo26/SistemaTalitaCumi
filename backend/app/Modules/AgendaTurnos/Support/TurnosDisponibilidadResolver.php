<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;

/**
 * Turnos que podrían entrar en conflicto con una reprogramación en una fecha.
 */
final class TurnosDisponibilidadResolver
{
    /**
     * @return list<TurnoExistente>
     */
    public function paraCambio(
        int $pacienteId,
        ?int $profesionalId,
        ?int $salaId,
        string $fecha,
    ): array {
        return Turno::query()
            ->where('fecha', $fecha)
            ->vigentes()
            ->where(static function ($query) use ($pacienteId, $profesionalId, $salaId): void {
                $query->where('paciente_id', $pacienteId);

                if ($profesionalId !== null) {
                    $query->orWhere('profesional_id', $profesionalId);
                }

                if ($salaId !== null) {
                    $query->orWhere('sala_id', $salaId);
                }
            })
            ->ordenado()
            ->get()
            ->map(static fn (Turno $turno): TurnoExistente => TurnoExistente::fromTurno($turno))
            ->all();
    }
}
