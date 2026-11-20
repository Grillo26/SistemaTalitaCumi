<?php

namespace App\Modules\Organizacion\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class SalaCreated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $salaId,
        public readonly array $snapshot,
        public readonly ?int $actorId,
    ) {}
}
