<?php

namespace App\Modules\AgendaTurnos\Exceptions;

/**
 * 07.05/RF-002 / 07.05/RN-002.
 *
 * El profesional no cubre ninguna especialidad requerida por el tipo.
 */
final class ProfesionalNoCubreRequisitoException extends AgendaTurnosDomainException
{
    public function __construct(public readonly int $profesionalId)
    {
        parent::__construct('El profesional seleccionado no cubre ninguna especialidad requerida por el diagnóstico.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_NO_CUBRE_REQUISITO';
    }

    public function context(): array
    {
        return ['profesional_id' => $this->profesionalId];
    }
}
