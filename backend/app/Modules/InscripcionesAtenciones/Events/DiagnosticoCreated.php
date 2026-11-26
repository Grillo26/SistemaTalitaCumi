<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Alta de un diagnóstico o evaluación.
 *
 * Incluye profesionales, snapshot de requisitos y omisión de entrevista para
 * que Pagos y la etapa 2 puedan consumir el evento sin consultar la Action.
 */
final class DiagnosticoCreated
{
    /**
     * @param  list<int>  $profesionalesIds
     * @param  list<array{especialidad_id: int, especialidad_nombre: string, cantidad_minima: int}>  $requisitos
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $diagnosticoId,
        public readonly int $pacienteId,
        public readonly int $sucursalId,
        public readonly int $servicioId,
        public readonly int $tipoDiagnosticoId,
        public readonly int $tarifaId,
        public readonly int $categoriaTarifaId,
        public readonly string $fechaInicio,
        public readonly string $fechaFin,
        public readonly string $montoEsperado,
        public readonly array $profesionalesIds,
        public readonly array $requisitos,
        public readonly ?int $entrevistaPreviaId,
        public readonly bool $omitioEntrevista,
        public readonly ?string $motivoOmisionEntrevista,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $snapshot,
    ) {}
}
