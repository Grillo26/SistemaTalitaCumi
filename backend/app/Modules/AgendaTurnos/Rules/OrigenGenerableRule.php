<?php

namespace App\Modules\AgendaTurnos\Rules;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;

/**
 * [Programación de Turnos] RF-002 / RF-010 / CA-005 / CU-03 FE-01.
 *
 * Solo se generan turnos desde una entidad en estado efectivo Activa. El
 * resto de estados bloquea con un mensaje que sugiere la acción concreta.
 */
final class OrigenGenerableRule
{
    public function permite(EstadoOrigenProgramacion $estado): bool
    {
        return $estado === EstadoOrigenProgramacion::ACTIVA;
    }

    public function mensaje(
        EstadoOrigenProgramacion $estado,
        TipoOrigenTurno $tipo,
    ): ?string {
        if ($estado === EstadoOrigenProgramacion::ACTIVA) {
            return null;
        }

        $sujeto = $tipo === TipoOrigenTurno::INSCRIPCION_SALA
            ? 'La inscripción'
            : 'El paquete';

        return match ($estado) {
            EstadoOrigenProgramacion::VENCIDA => "{$sujeto} está vencido/a. Renovelo antes de generar turnos.",
            EstadoOrigenProgramacion::EN_PAUSA => "{$sujeto} está en pausa. Reactívelo antes de generar turnos.",
            EstadoOrigenProgramacion::RENOVADA => "{$sujeto} fue renovado/a. Genere los turnos desde la entidad vigente.",
            EstadoOrigenProgramacion::RETIRADA => "{$sujeto} fue retirado/a. No se pueden generar turnos.",
            default => 'No se pudo determinar el estado de la entidad origen.',
        };
    }
}
