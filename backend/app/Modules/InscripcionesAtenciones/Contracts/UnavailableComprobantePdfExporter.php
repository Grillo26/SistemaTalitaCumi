<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableComprobantePdfExporter implements ComprobantePdfExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function generar(int $inscripcionId): StreamedResponse
    {
        throw new ModuloNoDisponibleException(
            'PDF',
            'La generación de PDF no está disponible: falta instalar la infraestructura PDF.',
        );
    }
}
