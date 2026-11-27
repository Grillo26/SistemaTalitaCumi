<?php

namespace App\Modules\InscripcionesAtenciones\Events;

use App\Modules\InscripcionesAtenciones\Enums\MotivoBajaAtencion;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;

/**
 * Baja de una atención (RF-011 / RN-009).
 */
final class AtencionRetirada
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly TipoAtencion $tipo,
        public readonly int $atencionId,
        public readonly int $sucursalId,
        public readonly int $pacienteId,
        public readonly MotivoBajaAtencion $motivo,
        public readonly ?string $motivoDetalle,
        public readonly string $fechaBaja,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
