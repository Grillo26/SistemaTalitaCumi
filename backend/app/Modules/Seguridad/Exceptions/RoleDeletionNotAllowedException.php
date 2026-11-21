<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * Los roles del catálogo no se eliminan (02/RN-17).
 */
final class RoleDeletionNotAllowedException extends SeguridadDomainException
{
    public function __construct(string $roleName)
    {
        parent::__construct(sprintf(
            'No se puede eliminar el rol "%s". El catálogo es fijo; solo se editan sus permisos.',
            $roleName,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'ROLE_DELETE_NOT_ALLOWED';
    }
}
