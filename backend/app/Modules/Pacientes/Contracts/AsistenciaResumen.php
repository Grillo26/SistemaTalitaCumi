<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Resumen de asistencia calculado por el módulo 07 (04.04/RF-008).
 */
final readonly class AsistenciaResumen
{
    public function __construct(
        public int $asistidos,
        public int $falto,
        public int $cancelados,
        public float $porcentaje,
        public ?string $periodoInicio,
        public ?string $periodoFin,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'asistidos' => $this->asistidos,
            'falto' => $this->falto,
            'cancelados' => $this->cancelados,
            'porcentaje' => $this->porcentaje,
            'periodo_inicio' => $this->periodoInicio,
            'periodo_fin' => $this->periodoFin,
        ];
    }
}
