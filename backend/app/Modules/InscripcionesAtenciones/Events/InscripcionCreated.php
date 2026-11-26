<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Alta de una inscripción a sala grupal.
 *
 * Payload apto para que Pagos y Caja cree la expectativa de pago cuando el
 * módulo exista. La Action no llama a Pagos.
 */
final class InscripcionCreated
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $inscripcionId,
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
        public readonly ?int $inscripcionOrigenId,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $snapshot,
    ) {}
}
