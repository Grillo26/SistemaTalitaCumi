<?php

namespace App\Modules\InscripcionesAtenciones\Events;

use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;

/**
 * Modificación de una atención (fechas, profesionales o notas).
 */
final class AtencionUpdated
{
    /**
     * @param  list<string>  $changedFields
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly TipoAtencion $tipo,
        public readonly int $atencionId,
        public readonly array $before,
        public readonly array $after,
        public readonly array $changedFields,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
