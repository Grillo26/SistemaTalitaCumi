<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Alta de un paquete de sesiones individuales.
 *
 * Payload para Agenda (patrón de días/hora/duración/profesional) y para Pagos
 * (monto total). La Action no llama a esos módulos.
 */
final class PaqueteCreated
{
    /**
     * @param  list<int>  $diasSemana
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $paqueteId,
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
        public readonly ?int $paqueteOrigenId,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $snapshot,
    ) {}
}
