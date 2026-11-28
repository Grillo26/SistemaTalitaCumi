<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class ExportacionCambiosNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'La exportación de cambios de turnos no está disponible: falta instalar la infraestructura PDF/Excel.',
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'EXPORTACION_CAMBIOS_NO_DISPONIBLE';
    }
}
