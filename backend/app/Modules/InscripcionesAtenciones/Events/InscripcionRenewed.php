<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Renovación de una inscripción (RF-010).
 */
final class InscripcionRenewed
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $inscripcionId,
        public readonly int $inscripcionOrigenId,
        public readonly int $pacienteId,
        public readonly int $tutorResponsableId,
        public readonly int $sucursalId,
        public readonly int $salaId,
        public readonly int $servicioId,
        public readonly int $tarifaId,
        public readonly int $categoriaTarifaId,
        public readonly string $fechaInicio,
        public readonly string $fechaFin,
        public readonly string $montoEsperado,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
