<?php

namespace App\Modules\InscripcionesAtenciones\Events;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaPaquete;

/**
 * Baja de un paquete (RF-014).
 *
 * Agenda y Turnos puede consumirlo para bloquear nuevos descuentos. La Action
 * no llama a Agenda.
 */
final class PaqueteRetirado
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $paqueteId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly int $servicioId,
        public readonly MotivoBajaPaquete $motivo,
        public readonly ?string $motivoDetalle,
        public readonly string $fechaBaja,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
