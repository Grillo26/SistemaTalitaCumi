<?php

namespace App\Modules\CatalogoServicios\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CategoriaTarifaCreated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function __construct(
        public readonly int $categoriaTarifaId,
        public readonly array $snapshot,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
