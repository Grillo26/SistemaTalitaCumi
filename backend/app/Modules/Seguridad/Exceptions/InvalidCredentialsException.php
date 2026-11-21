<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * Credenciales inválidas, cuenta inactiva o bloqueada (02/RF-A11).
 *
 * El mensaje es siempre el mismo para no revelar si el usuario existe.
 */
final class InvalidCredentialsException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('Usuario o contraseña incorrectos.');
    }

    public function status(): int
    {
        return 401;
    }

    public function errorCode(): string
    {
        return 'INVALID_CREDENTIALS';
    }
}
