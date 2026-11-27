<?php

namespace App\Modules\AgendaTurnos\Exceptions;

use App\Modules\AgendaTurnos\Enums\TipoOrigenTurno;

final class OrigenNoEncontradoException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly TipoOrigenTurno $tipo,
        public readonly int $origenId,
    ) {
        parent::__construct("No se encontró la entidad origen ({$tipo->label()} #{$origenId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ORIGEN_NO_ENCONTRADO';
    }
}
