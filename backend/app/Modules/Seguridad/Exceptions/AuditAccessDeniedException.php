<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * El actor no tiene alcance para ver el evento (02/RF-B06 / 02/RF-B14).
 */
final class AuditAccessDeniedException extends SeguridadDomainException
{
    public function __construct()
    {
        parent::__construct('No tenés acceso a este evento de bitácora.');
    }

    public function status(): int
    {
        return 403;
    }

    public function errorCode(): string
    {
        return 'AUDIT_ACCESS_DENIED';
    }
}
