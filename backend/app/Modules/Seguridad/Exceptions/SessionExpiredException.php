<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * La sesión expiró por inactividad (02/RF-A07).
 */
final class SessionExpiredException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('Tu sesión expiró por inactividad. Iniciá sesión nuevamente.');
    }

    public function status(): int
    {
        return 401;
    }

    public function errorCode(): string
    {
        return 'SESSION_EXPIRED';
    }
}
