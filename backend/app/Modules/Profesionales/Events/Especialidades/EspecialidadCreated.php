<?php

namespace App\Modules\Profesionales\Events\Especialidades;

use Illuminate\Foundation\Events\Dispatchable;

final class EspecialidadCreated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $especialidadId,
        public readonly array $snapshot,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
