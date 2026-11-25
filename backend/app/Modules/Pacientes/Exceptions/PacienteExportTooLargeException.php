<?php

namespace App\Modules\Pacientes\Exceptions;

final class PacienteExportTooLargeException extends PacientesDomainException
{
    public function __construct(int $maxRows)
    {
        parent::__construct("La exportación supera el límite de {$maxRows} filas. Aplicá más filtros.");
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PACIENTES_EXPORT_TOO_LARGE';
    }
}
