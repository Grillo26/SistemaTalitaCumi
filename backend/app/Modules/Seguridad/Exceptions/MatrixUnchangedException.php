<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * La matriz enviada no cambia respecto de la vigente (02/RF-R04).
 */
final class MatrixUnchangedException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('No hay cambios en los permisos del rol.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'MATRIX_UNCHANGED';
    }
}
