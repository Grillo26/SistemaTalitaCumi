<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Exceptions\ExportacionAsistenciaNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableAsistenciaPdfExporter implements AsistenciaPdfExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function exportar(array $filtros, int $usuarioId): StreamedResponse
    {
        throw new ExportacionAsistenciaNoDisponibleException;
    }
}
