<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Exceptions\ExportacionCambiosNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableCambiosTurnosExporter implements CambiosTurnosExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function exportar(array $filtros, int $usuarioId): StreamedResponse
    {
        throw new ExportacionCambiosNoDisponibleException;
    }
}
