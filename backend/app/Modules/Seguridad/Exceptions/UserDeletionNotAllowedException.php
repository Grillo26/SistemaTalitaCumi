<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * No se permite eliminar usuarios físicamente (02/RF-U11 / 02/RN-05).
 */
final class UserDeletionNotAllowedException extends SeguridadDomainException
{
    public function __construct(string $userName)
    {
        parent::__construct(sprintf(
            'No se puede eliminar el usuario "%s". Usá la acción Desactivar para conservar el historial.',
            $userName,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'USER_DELETE_NOT_ALLOWED';
    }
}
