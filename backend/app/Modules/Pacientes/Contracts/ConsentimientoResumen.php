<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Resumen no sensible de un consentimiento vigente para 06 y 11.
 */
final readonly class ConsentimientoResumen
{
    public function __construct(
        public int $consentimientoId,
        public int $pacienteId,
        public int $fichaIngresoId,
        public string $tipoCodigo,
        public int $tutorId,
        public string $fecha,
        public string $resultado,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'consentimiento_id' => $this->consentimientoId,
            'paciente_id' => $this->pacienteId,
            'ficha_ingreso_id' => $this->fichaIngresoId,
            'tipo_codigo' => $this->tipoCodigo,
            'tutor_id' => $this->tutorId,
            'fecha' => $this->fecha,
            'resultado' => $this->resultado,
        ];
    }
}
