<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Models\Turno;

/**
 * Entrada validada para reprogramar un turno.
 */
final readonly class ReprogramacionInput
{
    public function __construct(
        public string $fecha,
        public string $horaInicio,
        public int $motivoId,
        public ?string $motivoTexto,
        public EstadoTurno $estadoEsperado,
        public bool $profesionalProporcionado = false,
        public ?int $profesionalId = null,
        public bool $salaProporcionada = false,
        public ?int $salaId = null,
        public bool $confirmarAdvertencias = false,
    ) {}
}
