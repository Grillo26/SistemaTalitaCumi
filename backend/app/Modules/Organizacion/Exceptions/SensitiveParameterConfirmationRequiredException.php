<?php

namespace App\Modules\Organizacion\Exceptions;

final class SensitiveParameterConfirmationRequiredException extends OrganizacionDomainException
{
    public function __construct(string $clave)
    {
        parent::__construct(
            "El parámetro '{$clave}' es sensible: confirmá el cambio e ingresá un motivo antes de guardar.",
        );
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PARAMETRO_SENSIBLE_SIN_CONFIRMACION';
    }
}
