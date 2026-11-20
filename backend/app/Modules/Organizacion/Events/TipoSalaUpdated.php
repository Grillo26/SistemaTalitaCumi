<?php

namespace App\Modules\Organizacion\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class TipoSalaUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @param  list<string>  $changedFields
     */
    public function __construct(
        public readonly int $tipoSalaId,
        public readonly array $before,
        public readonly array $after,
        public readonly array $changedFields,
        public readonly ?int $actorId,
    ) {}
}
