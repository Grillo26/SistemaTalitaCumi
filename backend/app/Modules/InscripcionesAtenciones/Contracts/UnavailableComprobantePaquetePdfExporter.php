<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableComprobantePaquetePdfExporter implements ComprobantePaquetePdfExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function generar(int $paqueteId): StreamedResponse
    {
        throw new ModuloNoDisponibleException(
            'PDF',
            'La generación de PDF no está disponible: falta instalar la infraestructura PDF.',
        );
    }
}
