<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobanteAtencionPdfExporterContract;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Generación del PDF del comprobante de atención (RF-012).
 */
final class GenerateComprobanteAtencionPdfQuery
{
    public function __construct(
        private readonly GetAtencionQuery $detalle,
        private readonly ComprobanteAtencionPdfExporterContract $exporter,
    ) {}

    public function __invoke(TipoAtencion $tipo, int $id, User $actor): StreamedResponse
    {
        ($this->detalle)($tipo, $id, $actor);

        return $this->exporter->generar($tipo, $id);
    }
}
