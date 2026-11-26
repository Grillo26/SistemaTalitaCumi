<?php

namespace App\Modules\InscripcionesAtenciones\Events;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBaja;

/**
 * Baja de una inscripción (RF-011).
 *
 * Agenda y Turnos puede consumirlo para bloquear nuevas asistencias. La
 * Action no llama a Agenda.
 */
final class InscripcionRetirada
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $inscripcionId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly int $salaId,
        public readonly MotivoBaja $motivo,
        public readonly ?string $motivoDetalle,
        public readonly string $fechaBaja,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
