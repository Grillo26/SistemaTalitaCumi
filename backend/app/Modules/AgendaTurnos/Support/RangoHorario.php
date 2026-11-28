<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Rango horario de la grilla. Siempre incluye la apertura/cierre configurados
 * y se expande si hay turnos fuera de ese horario.
 */
final readonly class RangoHorario
{
    public function __construct(
        public string $horaInicio,
        public string $horaFin,
    ) {}

    /**
     * @return array{hora_inicio: string, hora_fin: string}
     */
    public function toArray(): array
    {
        return [
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin,
        ];
    }
}
