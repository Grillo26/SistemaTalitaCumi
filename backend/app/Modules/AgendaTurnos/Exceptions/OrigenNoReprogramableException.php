<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\EstadoOrigenProgramacion;
use App\Modules\AgendaTurnos\Rules\OrigenReprogramableRule;

final class OrigenNoReprogramableException extends AgendaTurnosDomainException
{
    public function __construct(public readonly EstadoOrigenProgramacion $estado)
    {
        parent::__construct(
            (new OrigenReprogramableRule)->mensaje($estado)
                ?? 'La entidad origen no permite reprogramar turnos.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ORIGEN_NO_REPROGRAMABLE';
    }
}
