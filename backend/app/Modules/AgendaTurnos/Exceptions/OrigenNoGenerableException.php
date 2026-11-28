<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;
use App\Modules\AgendaTurnos\Rules\OrigenGenerableRule;

final class OrigenNoGenerableException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly EstadoOrigenProgramacion $estado,
        public readonly TipoOrigenTurno $tipo,
    ) {
        parent::__construct(
            (new OrigenGenerableRule)->mensaje($estado, $tipo)
                ?? 'La entidad origen no permite generar turnos.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ORIGEN_NO_GENERABLE';
    }
}
