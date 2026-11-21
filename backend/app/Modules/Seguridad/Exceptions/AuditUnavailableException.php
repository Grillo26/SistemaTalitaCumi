<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * La bitácora no pudo registrarse (RT-12, fail-closed).
 */
final class AuditUnavailableException extends SeguridadDomainException
{
    public function __construct(?\Throwable $previous = null)
    {
        parent::__construct('No se pudo registrar la auditoría. La operación fue cancelada.', 0, $previous);
    }

    public function status(): int
    {
        return 503;
    }

    public function errorCode(): string
    {
        return 'AUDIT_UNAVAILABLE';
    }
}
