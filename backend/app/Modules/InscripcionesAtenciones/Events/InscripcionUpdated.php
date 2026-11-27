<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Modificación de una inscripción (RF-009).
 */
final class InscripcionUpdated
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly int $inscripcionId,
        public readonly array $before,
        public readonly array $after,
        public readonly array $changedFields,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
