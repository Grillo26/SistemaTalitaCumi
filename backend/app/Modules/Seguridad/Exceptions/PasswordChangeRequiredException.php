<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * El usuario debe cambiar la contraseña antes de operar (02/RF-A13 / 02/RN-07).
 */
final class PasswordChangeRequiredException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('Debés cambiar tu contraseña antes de continuar.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'PASSWORD_CHANGE_REQUIRED';
    }
}
