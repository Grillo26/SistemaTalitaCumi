<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\EstadoTurno;

final class EstadoTurnoDesactualizadoException extends AgendaTurnosDomainException
{
    public function __construct(public readonly EstadoTurno $estadoActual)
    {
        parent::__construct(
            "El turno cambió de estado a «{$estadoActual->label()}». Recargá la ficha antes de continuar.",
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ESTADO_TURNO_DESACTUALIZADO';
    }

    public function context(): array
    {
        return [
            'estado_actual' => $this->estadoActual->value,
            'estado_actual_label' => $this->estadoActual->label(),
        ];
    }
}
