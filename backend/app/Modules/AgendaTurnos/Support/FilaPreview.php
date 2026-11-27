<?php

namespace App\Modules\AgendaTurnos\Support;

final class FilaPreview
{
    /**
     * @param  list<Hallazgo>  $hallazgos
     */
    public function __construct(
        public readonly string $fecha,
        public readonly string $horaInicio,
        public readonly string $horaFin,
        public string $estado,
        public array $hallazgos = [],
        public bool $generable = true,
    ) {}

    public function agregarHallazgo(Hallazgo $hallazgo): void
    {
        $this->hallazgos[] = $hallazgo;

        if ($hallazgo->severidad->value === 'bloqueo') {
            $this->generable = false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'fecha' => $this->fecha,
            'hora_inicio' => $this->horaInicio,
            'hora_fin' => $this->horaFin,
            'estado' => $this->estado,
            'generable' => $this->generable,
            'hallazgos' => array_map(
                static fn (Hallazgo $hallazgo): array => $hallazgo->toArray(),
                $this->hallazgos,
            ),
        ];
    }
}
