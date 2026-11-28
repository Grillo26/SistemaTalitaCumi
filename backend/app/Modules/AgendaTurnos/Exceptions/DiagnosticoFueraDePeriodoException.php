<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-002 / 07.05/RN-001.
 *
 * El turno debe caer dentro del período del diagnóstico.
 */
final class DiagnosticoFueraDePeriodoException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly string $fecha,
        public readonly string $fechaInicio,
        public readonly string $fechaFin,
    ) {
        parent::__construct(sprintf(
            'La fecha %s está fuera del período del diagnóstico (%s a %s).',
            $fecha,
            $fechaInicio,
            $fechaFin,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'DIAGNOSTICO_FUERA_DE_PERIODO';
    }

    public function context(): array
    {
        return [
            'fecha' => $this->fecha,
            'fecha_inicio' => $this->fechaInicio,
            'fecha_fin' => $this->fechaFin,
        ];
    }
}
