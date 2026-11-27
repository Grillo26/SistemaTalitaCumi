<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Exceptions\ModuloNoDisponibleException;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UnavailableComprobanteAtencionPdfExporter implements ComprobanteAtencionPdfExporterContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function generar(TipoAtencion $tipo, int $atencionId): StreamedResponse
    {
        throw new ModuloNoDisponibleException(
            'PDF',
            'La generación de PDF no está disponible: falta instalar la infraestructura PDF.',
        );
    }
}
