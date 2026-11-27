<?php

namespace App\Modules\AgendaTurnos\Events;

final class FeriadoDesactivado
{
    /**
     * @param  array<string, mixed>  $before
     */
    public function __construct(
        public readonly int $feriadoId,
        public readonly array $before,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
