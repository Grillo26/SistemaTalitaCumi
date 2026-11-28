<?php

namespace App\Modules\AgendaTurnos\Support;

final readonly class TurnoCandidato
{
    public function __construct(
        public int $pacienteId,
        public ?int $profesionalId,
        public ?int $salaId,
        public int $servicioId,
        public string $fecha,
        public string $horaInicio,
        public string $horaFin,
        public ?string $sesionKey = null,
    ) {}
}
