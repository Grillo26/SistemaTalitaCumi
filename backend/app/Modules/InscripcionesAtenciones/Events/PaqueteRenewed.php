<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Renovación de un paquete (RF-013).
 */
final class PaqueteRenewed
{
    /**
     * @param  list<int>  $diasSemana
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $paqueteId,
        public readonly int $paqueteOrigenId,
        public readonly int $pacienteId,
        public readonly int $tutorResponsableId,
        public readonly int $sucursalId,
        public readonly int $servicioId,
        public readonly ?int $profesionalId,
        public readonly int $tarifaId,
        public readonly int $categoriaTarifaId,
        public readonly int $cantidadSesiones,
        public readonly array $diasSemana,
        public readonly string $hora,
        public readonly int $duracionMinutos,
        public readonly string $fechaInicio,
        public readonly string $fechaFin,
        public readonly string $montoEsperado,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
