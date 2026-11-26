<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class ServicioDeletionNotAllowedException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'Los servicios no se eliminan. Desactivalo para conservar tarifas, inscripciones y turnos históricos.',
        );
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'SERVICIO_DELETE_NOT_ALLOWED';
    }
}
