<?php

namespace App\Modules\InscripcionesAtenciones\Events;

/**
 * Vinculación de una entrevista a un diagnóstico ya registrado (CU-03 FA-01).
 */
final class EntrevistaVinculada
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $diagnosticoId,
        public readonly int $entrevistaId,
        public readonly int $actorId,
        public readonly ?string $ip,
        public readonly array $before,
        public readonly array $after,
    ) {}
}
