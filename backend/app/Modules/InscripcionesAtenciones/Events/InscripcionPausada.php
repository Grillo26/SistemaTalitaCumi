<?php

namespace App\Modules\InscripcionesAtenciones\Events;

use App\Modules\InscripcionesAtenciones\Enums\EstadoInscripcion;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

/**
 * [Estados Paciente e Inscripción] RF-004 / RF-009.
 */
final readonly class InscripcionPausada
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public TipoEntidadVigencia $tipo,
        public int $entidadId,
        public int $sucursalId,
        public int $pacienteId,
        public EstadoInscripcion $from,
        public EstadoInscripcion $to,
        public string $motivoCodigo,
        public ?string $motivoTexto,
        public int $actorId,
        public ?string $ip,
        public array $before,
        public array $after,
    ) {}
}
