<?php

namespace App\Modules\CatalogoServicios\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class AreaColorChanged
{
    use Dispatchable;

    public function __construct(
        public readonly int $areaId,
        public readonly string $beforeColor,
        public readonly string $afterColor,
        public readonly ?int $actorId,
        public readonly ?string $ip,
    ) {}
}
