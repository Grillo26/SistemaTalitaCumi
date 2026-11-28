<?php

namespace App\Modules\AgendaTurnos\Contracts;

use App\Modules\AgendaTurnos\Exceptions\ExportacionAgendaNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableAgendaExporter implements AgendaExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function exportar(array $datos, int $usuarioId): StreamedResponse
    {
        throw new ExportacionAgendaNoDisponibleException;
    }
}
