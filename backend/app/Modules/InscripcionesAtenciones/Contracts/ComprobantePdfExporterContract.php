<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación PDF del comprobante (RF-012).
 *
 * La infraestructura PDF no está instalada. La vista imprimible HTML es la
 * alternativa operativa mientras este contrato siga en stub.
 */
interface ComprobantePdfExporterContract
{
    public function disponible(): bool;

    public function generar(int $inscripcionId): StreamedResponse;
}
