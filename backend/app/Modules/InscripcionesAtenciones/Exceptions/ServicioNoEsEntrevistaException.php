<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class ServicioNoEsEntrevistaException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly string $servicioNombre)
    {
        parent::__construct("El servicio \"{$servicioNombre}\" no es de tipo entrevista.");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'SERVICIO_NO_ES_ENTREVISTA';
    }
}
