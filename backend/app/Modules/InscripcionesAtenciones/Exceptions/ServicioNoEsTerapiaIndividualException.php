<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class ServicioNoEsTerapiaIndividualException extends InscripcionesAtencionesDomainException
{
    public function __construct(private readonly string $servicioNombre)
    {
        parent::__construct(
            sprintf('El servicio "%s" no es del tipo Terapia individual.', $this->servicioNombre),
        );
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'SERVICIO_NO_ES_TERAPIA_INDIVIDUAL';
    }
}
