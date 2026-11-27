<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableAtencionExporter implements AtencionExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function exportar(array $filtros, int $usuarioId): StreamedResponse
    {
        throw new ModuloNoDisponibleException(
            'Exportación',
            'La exportación de atenciones no está disponible: falta instalar la infraestructura PDF/Excel.',
        );
    }
}
