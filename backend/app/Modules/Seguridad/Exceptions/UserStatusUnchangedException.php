<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * El usuario ya tiene el estado solicitado (02/RF-U09).
 */
final class UserStatusUnchangedException extends SeguridadDomainException
{
    public function __construct(bool $targetStatus)
    {
        parent::__construct($targetStatus
            ? 'El usuario ya se encuentra activo.'
            : 'El usuario ya se encuentra inactivo.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'USER_STATUS_UNCHANGED';
    }
}
