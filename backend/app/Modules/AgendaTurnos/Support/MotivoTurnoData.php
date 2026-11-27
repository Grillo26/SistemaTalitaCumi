<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\OrigenMotivoTurno;

/**
 * Datos puros de un motivo para las Rules de 7.3.
 */
final readonly class MotivoTurnoData
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nombre,
        public bool $aplicaReprogramar,
        public bool $aplicaCancelar,
        public ?OrigenMotivoTurno $origen,
        public ?bool $descuentaSaldo,
        public bool $requiereTexto,
        public bool $activo,
    ) {}
}
