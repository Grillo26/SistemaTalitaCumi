<?php

namespace App\Modules\Profesionales\Events\Directorio;

use Illuminate\Foundation\Events\Dispatchable;

final class ProfesionalSucursalChanged
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $profesionalId,
        public readonly int $sucursalId,
        public readonly ?array $before,
        public readonly array $after,
        public readonly string $action,
        public readonly ?string $reason,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
