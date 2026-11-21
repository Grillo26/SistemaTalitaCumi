<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * El usuario está inactivo y no puede operar (02/RN-06).
 */
final class InactiveUserException extends SeguridadDomainException
{
    public function __construct(int $userId, string $userName)
    {
        parent::__construct(sprintf(
            'El usuario "%s" está inactivo y no puede realizar nuevas operaciones.',
            $userName,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'USER_INACTIVE';
    }
}
