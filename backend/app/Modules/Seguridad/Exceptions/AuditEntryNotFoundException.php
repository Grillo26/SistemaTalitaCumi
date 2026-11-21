<?php

namespace App\Modules\Seguridad\Exceptions;

/**
 * Entrada de bitácora inexistente (02/RF-B05).
 */
final class AuditEntryNotFoundException extends SeguridadDomainException
{
    public function __construct(int $auditId)
    {
        parent::__construct("No se encontró el evento de bitácora solicitado (ID {$auditId}).");
    }

    public function status(): int
    {
        return 404;
    }

    public function errorCode(): string
    {
        return 'AUDIT_ENTRY_NOT_FOUND';
    }
}
