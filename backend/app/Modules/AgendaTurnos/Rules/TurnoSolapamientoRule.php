<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;
use App\Modules\AgendaTurnos\Support\Hallazgo;
use App\Modules\AgendaTurnos\Support\TurnoCandidato;
use App\Modules\AgendaTurnos\Support\TurnoExistente;

/**
 * [Programación de Turnos] RF-004 / RF-005 / RF-006 / RT-002.
 *
 * Solapamiento por rango de tiempo: inicio_a < fin_b && inicio_b < fin_a.
 * Los turnos contiguos no se solapan. Se ignoran los turnos cancelados y los
 * reprogramados (el turno vigente es el destino de la reprogramación).
 *
 * - Paciente: bloqueo siempre.
 * - Profesional: bloqueo contra otra sesión grupal; la misma sesión no cuenta.
 * - Sala: advertencia contra otra sesión grupal; la misma sesión no cuenta.
 *
 * La regla recibe los turnos ya consultados: no accede a la base de datos.
 */
final class TurnoSolapamientoRule
{
    /**
     * @param  list<TurnoExistente>  $existentes
     * @param  list<int>  $excluirTurnoIds
     * @return list<Hallazgo>
     */
    public function evaluar(
        TurnoCandidato $candidato,
        array $existentes,
        array $excluirTurnoIds = [],
    ): array {
        $hallazgos = [];
        $vistos = [];

        foreach ($existentes as $existente) {
            if (in_array($existente->id, $excluirTurnoIds, true)) {
                continue;
            }

            if ($this->estadoIgnorado($existente->estado)) {
                continue;
            }

            if ($existente->fecha !== $candidato->fecha) {
                continue;
            }

            if (! $this->seSolapan($candidato, $existente)) {
                continue;
            }

            $mismaSesion = $this->mismaSesion($candidato, $existente);

            if ($existente->pacienteId === $candidato->pacienteId) {
                $this->agregar(
                    $hallazgos,
                    $vistos,
                    new Hallazgo(
                        tipo: 'paciente',
                        severidad: SeveridadHallazgo::BLOQUEO,
                        mensaje: 'El paciente ya tiene un turno en ese horario.',
                        fecha: $candidato->fecha,
                        horaInicio: $candidato->horaInicio,
                        turnoId: $existente->id,
                    ),
                );
            }

            if (
                $candidato->profesionalId !== null
                && $existente->profesionalId === $candidato->profesionalId
                && ! $mismaSesion
            ) {
                $this->agregar(
                    $hallazgos,
                    $vistos,
                    new Hallazgo(
                        tipo: 'profesional',
                        severidad: SeveridadHallazgo::BLOQUEO,
                        mensaje: 'El profesional ya tiene un turno en ese horario.',
                        fecha: $candidato->fecha,
                        horaInicio: $candidato->horaInicio,
                        turnoId: $existente->id,
                    ),
                );
            }

            if (
                $candidato->salaId !== null
                && $existente->salaId === $candidato->salaId
                && ! $mismaSesion
            ) {
                $this->agregar(
                    $hallazgos,
                    $vistos,
                    new Hallazgo(
                        tipo: 'sala',
                        severidad: SeveridadHallazgo::ADVERTENCIA,
                        mensaje: 'La sala tiene otro turno en ese horario.',
                        fecha: $candidato->fecha,
                        horaInicio: $candidato->horaInicio,
                        turnoId: $existente->id,
                    ),
                );
            }
        }

        return $hallazgos;
    }

    private function estadoIgnorado(EstadoTurno $estado): bool
    {
        return $estado === EstadoTurno::CANCELADO
            || $estado === EstadoTurno::REPROGRAMADO;
    }

    private function seSolapan(TurnoCandidato $candidato, TurnoExistente $existente): bool
    {
        return $this->aMinutos($candidato->horaInicio) < $this->aMinutos($existente->horaFin)
            && $this->aMinutos($existente->horaInicio) < $this->aMinutos($candidato->horaFin);
    }

    private function mismaSesion(TurnoCandidato $candidato, TurnoExistente $existente): bool
    {
        $claveCandidato = SesionGrupalRule::clave(
            $candidato->salaId,
            $candidato->fecha,
            $candidato->horaInicio,
            $candidato->horaFin,
            $candidato->servicioId,
        );

        return $claveCandidato !== null
            && $existente->sesionKey !== null
            && SesionGrupalRule::misma($claveCandidato, $existente->sesionKey);
    }

    /**
     * @param  list<Hallazgo>  $hallazgos
     * @param  array<string, true>  $vistos
     */
    private function agregar(array &$hallazgos, array &$vistos, Hallazgo $hallazgo): void
    {
        $clave = $hallazgo->tipo.'|'.$hallazgo->turnoId;

        if (array_key_exists($clave, $vistos)) {
            return;
        }

        $vistos[$clave] = true;
        $hallazgos[] = $hallazgo;
    }

    private function aMinutos(string $hora): int
    {
        $partes = explode(':', $hora);

        return ((int) ($partes[0] ?? 0)) * 60 + ((int) ($partes[1] ?? 0));
    }
}
