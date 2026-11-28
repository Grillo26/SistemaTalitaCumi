<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Exceptions\ExportacionDiagnosticosNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableDiagnosticosPdfExporter implements DiagnosticosPdfExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function exportar(array $filtros, int $usuarioId): StreamedResponse
    {
        throw new ExportacionDiagnosticosNoDisponibleException;
    }
}
