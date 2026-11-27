<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class ConfiguracionAgendaIncompletaException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly string $detalle = 'Falta configuración de agenda o sus valores no son válidos.',
    ) {
        parent::__construct($detalle);
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'CONFIGURACION_AGENDA_INCOMPLETA';
    }
}
