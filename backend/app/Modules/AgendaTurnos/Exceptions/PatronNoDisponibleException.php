<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class PatronNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct(
        public readonly string $detalle = 'La entidad origen no tiene un patrón de días y hora configurado.',
    ) {
        parent::__construct($detalle);
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PATRON_NO_DISPONIBLE';
    }
}
