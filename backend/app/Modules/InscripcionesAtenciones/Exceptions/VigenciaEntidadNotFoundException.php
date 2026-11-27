<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

final class VigenciaEntidadNotFoundException extends InscripcionesAtencionesDomainException
{
    public function __construct(public readonly TipoEntidadVigencia $tipo, public readonly int $entidadId)
    {
        parent::__construct("No se encontró {$tipo->label()} #{$entidadId}.");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'VIGENCIA_ENTIDAD_NOT_FOUND';
    }
}
