<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Resumen de asistencia de los turnos de una inscripción (RF-014).
 */
final readonly class AsistenciaInscripcionResumen
{
    public function __construct(
        public int $asistencias,
        public int $faltas,
        public int $cancelados,
        public int $total,
        public ?string $periodoDesde = null,
        public ?string $periodoHasta = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'asistencias' => $this->asistencias,
            'faltas' => $this->faltas,
            'cancelados' => $this->cancelados,
            'total' => $this->total,
            'periodo_desde' => $this->periodoDesde,
            'periodo_hasta' => $this->periodoHasta,
        ];
    }
}
