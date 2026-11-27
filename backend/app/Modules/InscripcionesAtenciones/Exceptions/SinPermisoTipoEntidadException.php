<?php

namespace App\Modules\InscripcionesAtenciones\Exceptions;

final class SinPermisoTipoEntidadException extends InscripcionesAtencionesDomainException
{
    public function __construct()
    {
        parent::__construct(
            'No tenés permiso para consultar inscripciones a salas ni paquetes de sesiones.',
        );
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'SIN_PERMISO_TIPO_ENTIDAD';
    }
}
