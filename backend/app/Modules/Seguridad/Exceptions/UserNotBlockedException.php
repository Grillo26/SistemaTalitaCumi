<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * La cuenta no está bloqueada (02/RF-A06).
 */
final class UserNotBlockedException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('La cuenta no está bloqueada.');
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'USER_NOT_BLOCKED';
    }
}
