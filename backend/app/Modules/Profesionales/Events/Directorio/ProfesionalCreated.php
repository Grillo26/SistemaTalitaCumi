<?php

namespace App\Modules\Profesionales\Events\Directorio;

use Illuminate\Foundation\Events\Dispatchable;

final class ProfesionalCreated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $profesionalId,
        public readonly array $snapshot,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
