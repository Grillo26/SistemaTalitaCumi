<?php

namespace App\Modules\Profesionales\Events\Especialidades;

use Illuminate\Foundation\Events\Dispatchable;

final class EspecialidadUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly int $especialidadId,
        public readonly array $before,
        public readonly array $after,
        public readonly array $changedFields,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
