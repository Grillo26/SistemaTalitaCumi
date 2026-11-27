<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Alta de una entrevista con los padres.
 *
 * El payload deja la expectativa de pago para el módulo 08. La Action no
 * llama a Pagos.
 */
final class EntrevistaCreated
{
    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $entrevistaId,
        public readonly int $pacienteId,
        public readonly int $tutorId,
        public readonly int $sucursalId,
        public readonly int $servicioId,
        public readonly int $tarifaId,
        public readonly int $categoriaTarifaId,
        public readonly string $fecha,
        public readonly string $montoEsperado,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $snapshot,
    ) {}
}
