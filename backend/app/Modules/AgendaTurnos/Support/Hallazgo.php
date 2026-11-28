<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Enums\SeveridadHallazgo;

final readonly class Hallazgo
{
    public function __construct(
        public string $tipo,
        public SeveridadHallazgo $severidad,
        public string $mensaje,
        public ?string $fecha = null,
        public ?string $horaInicio = null,
        public ?int $turnoId = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'tipo' => $this->tipo,
            'severidad' => $this->severidad->value,
            'severidad_label' => $this->severidad->label(),
            'mensaje' => $this->mensaje,
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
            'turno_id' => $this->turnoId,
        ];
    }
}
