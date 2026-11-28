<?php

namespace App\Modules\AgendaTurnos\Events;

use App\Modules\AgendaTurnos\Support\MotivoTurnoData;

/**
 * Turno reprogramado: original marcado y nuevo turno creado.
 */
final class TurnoReprogramado
{
    /**
     * @param  array<string, mixed>  $original
     * @param  array<string, mixed>  $nuevo
     * @param  list<string>  $advertencias
     */
    public function __construct(
        public readonly int $turnoOriginalId,
        public readonly int $turnoNuevoId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly MotivoTurnoData $motivo,
        public readonly array $original,
        public readonly array $nuevo,
        public readonly array $advertencias,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
