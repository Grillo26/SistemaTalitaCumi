<?php

namespace App\Modules\CatalogoServicios\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class CategoriaTarifaUpdated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $categoriaTarifaId,
        public readonly array $before,
        public readonly array $after,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
