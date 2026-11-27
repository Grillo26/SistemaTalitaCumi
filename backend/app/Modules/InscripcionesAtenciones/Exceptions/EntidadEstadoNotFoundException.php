<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

/**
 * [Estados Paciente e Inscripción] RF-011.
 */
final class EntidadEstadoNotFoundException extends InscripcionesAtencionesDomainException
{
    public function __construct(
        public readonly TipoEntidadVigencia $tipo,
        public readonly int $entidadId,
    ) {
        parent::__construct("No se encontró {$tipo->label()} #{$entidadId}.");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ENTIDAD_ESTADO_NOT_FOUND';
    }
}
