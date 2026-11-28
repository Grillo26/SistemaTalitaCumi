<?php

namespace App\Modules\AgendaTurnos\Exceptions;

final class CatalogoNoDisponibleException extends AgendaTurnosDomainException
{
    public function __construct()
    {
        parent::__construct(
            'No se pudo aplicar el filtro por área porque el catálogo de servicios no está disponible.',
        );
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'CATALOGO_NO_DISPONIBLE';
    }
}
