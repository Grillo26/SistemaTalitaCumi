<?php

namespace App\Modules\AgendaTurnos\Events;

final class FeriadoCreado
{
    public function __construct(
        public readonly int $feriadoId,
        public readonly string $fecha,
        public readonly string $descripcion,
        public readonly ?int $sucursalId,
        public readonly int $actorId,
        public readonly ?string $ip,
    ) {}
}
