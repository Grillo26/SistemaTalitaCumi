<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Patrón de días, hora y duración con el que se calculan los turnos.
 */
final readonly class PatronProgramacion
{
    /**
     * @param  list<int>  $diasSemana
     */
    public function __construct(
        public array $diasSemana,
        public string $horaInicio,
        public int $duracionMinutos,
        public ?int $profesionalId = null,
    ) {}

    public function horaFin(): string
    {
        $partes = explode(':', $this->horaInicio);
        $minutos = ((int) ($partes[0] ?? 0)) * 60 + ((int) ($partes[1] ?? 0));
        $minutos += $this->duracionMinutos;

        return sprintf('%02d:%02d', intdiv($minutos, 60) % 24, $minutos % 60);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'dias_semana' => $this->diasSemana,
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin(),
            'duracion_minutos' => $this->duracionMinutos,
            'profesional_id' => $this->profesionalId,
        ];
    }
}
