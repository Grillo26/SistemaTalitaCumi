<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-001 / 07.05/RN-009.
 *
 * Solo se puede programar a profesionales ya asociados al diagnóstico por
 * 06.03.
 */
final class ProfesionalNoAsignadoAlDiagnosticoException extends AgendaTurnosDomainException
{
    public function __construct(public readonly int $profesionalId)
    {
        parent::__construct('El profesional seleccionado no está asociado al diagnóstico.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_NO_ASIGNADO_AL_DIAGNOSTICO';
    }

    public function context(): array
    {
        return ['profesional_id' => $this->profesionalId];
    }
}
