<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Models\Turno;

/**
 * Resultado puro de una cancelación individual.
 */
final readonly class CancelacionCalculada
{
    /**
     * @param  array<string, mixed>  $turno
     * @param  array<string, mixed>  $saldo
     */
    public function __construct(
        public Turno $modelo,
        public MotivoTurnoData $motivo,
        public bool $consumeSaldo,
        public array $turnoData,
        public array $saldo,
    ) {}
}
