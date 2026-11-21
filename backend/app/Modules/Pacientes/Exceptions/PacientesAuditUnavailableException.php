<?php

namespace App\Modules\Pacientes\Exceptions;

use Throwable;

final class PacientesAuditUnavailableException extends PacientesDomainException
{
    public function __construct(Throwable $previous)
    {
        parent::__construct('No se pudo registrar la operación en la bitácora. La operación fue cancelada.', 0, $previous);
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
