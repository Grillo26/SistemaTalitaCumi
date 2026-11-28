<?php

namespace App\Modules\AgendaTurnos\Events;

final class FeriadoActualizado
{
    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $feriadoId,
        public readonly array $before,
        public readonly array $after,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
