<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class TurnosYaGeneradosException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly int $cantidadTurnos,
    ) {
        parent::__construct(
            'La entidad origen ya tiene turnos generados. Regenerá los turnos para reemplazar los futuros programados.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'TURNOS_YA_GENERADOS';
    }

    public function context(): array
    {
        return [
            'cantidad_turnos' => $this->cantidadTurnos,
            'regenerar_disponible' => true,
        ];
    }
}
