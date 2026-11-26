<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\ComprobantePaquetePdfExporterContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF del comprobante de paquete; pendiente de infraestructura.
 */
final class GeneratePaqueteComprobantePdfQuery
{
    public function __construct(
        private readonly GetPaqueteQuery $detalle,
        private readonly ComprobantePaquetePdfExporterContract $exporter,
    ) {}

    public function __invoke(int $id, User $actor): StreamedResponse
    {
        ($this->detalle)($id, $actor);

        return $this->exporter->generar($id);
    }
}
