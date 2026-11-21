<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * El usuario está fuera del alcance por sucursal del actor (02/RF-R09).
 */
final class UserAccessDeniedException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('No tenés acceso a este usuario.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'USER_ACCESS_DENIED';
    }
}
