<?php

namespace App\Modules\CatalogoServicios\Exceptions;

final class CatalogoConcurrencyException extends CatalogoServiciosDomainException
{
    public function __construct()
    {
        parent::__construct('El registro fue modificado por otra persona. Recargá los datos e intentá nuevamente.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'VERSION_CONFLICT';
    }
}
