<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Modificación de un paquete (RF-012).
 */
final class PaqueteUpdated
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly int $paqueteId,
        public readonly array $before,
        public readonly array $after,
        public readonly array $changedFields,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
