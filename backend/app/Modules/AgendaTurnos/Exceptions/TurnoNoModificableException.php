<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

final class TurnoNoModificableException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly EstadoTurno $estadoActual,
        public readonly string $detalle,
    ) {
        parent::__construct($detalle);
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TURNO_NO_MODIFICABLE';
    }

    public function context(): array
    {
        return [
            'estado_actual' => $this->estadoActual->value,
            'estado_actual_label' => $this->estadoActual->label(),
        ];
    }
}
