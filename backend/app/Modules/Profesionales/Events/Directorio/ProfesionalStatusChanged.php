<?php

namespace App\Modules\Profesionales\Events\Directorio;

use Illuminate\Foundation\Events\Dispatchable;

final class ProfesionalStatusChanged
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $profesionalId,
        public readonly array $before,
        public readonly array $after,
        public readonly bool $activo,
        public readonly string $reason,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
