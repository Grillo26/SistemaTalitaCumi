<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Día de la semana mostrado por la agenda.
 */
final readonly class DiaAgenda
{
    public function __construct(
        public string $fecha,
        public int $diaSemana,
        public bool $visible,
        public bool $esHoy,
    ) {}

    public function nombre(): string
    {
        return match ($this->diaSemana) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miércoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sábado',
            7 => 'Domingo',
            default => '',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fecha' => $this->fecha,
            'nombre' => $this->nombre(),
            'dia_semana' => $this->diaSemana,
            'visible' => $this->visible,
            'es_hoy' => $this->esHoy,
        ];
    }
}
