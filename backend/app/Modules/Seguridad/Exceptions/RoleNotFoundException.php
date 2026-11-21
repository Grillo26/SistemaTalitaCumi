<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * Rol inexistente (02/RF-R01).
 */
final class RoleNotFoundException extends SeguridadDomainException
{
    public function __construct(int $roleId)
    {
        parent::__construct("No se encontró el rol solicitado (ID {$roleId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'ROLE_NOT_FOUND';
    }
}
