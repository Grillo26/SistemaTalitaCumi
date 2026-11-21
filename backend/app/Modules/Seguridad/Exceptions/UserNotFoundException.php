<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * Usuario inexistente (02/RF-U07).
 */
final class UserNotFoundException extends SeguridadDomainException
{
    public function __construct(int $usuarioId)
    {
        parent::__construct("No se encontró el usuario solicitado (ID {$usuarioId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'USER_NOT_FOUND';
    }
}
