<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobantePdfExporterContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF del comprobante (RF-012). Pendiente de infraestructura PDF.
 */
final class GenerateComprobantePdfQuery
{
    public function __construct(
        private readonly GetInscripcionQuery $detalle,
        private readonly ComprobantePdfExporterContract $exporter,
    ) {}

    public function __invoke(int $id, User $actor): StreamedResponse
    {
        ($this->detalle)($id, $actor);

        return $this->exporter->generar($id);
    }
}
