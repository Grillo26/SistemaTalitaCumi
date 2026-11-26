<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Comprobante PDF no fiscal de una entrevista o diagnóstico (RF-012).
 */
interface ComprobanteAtencionPdfExporterContract
{
    public function disponible(): bool;

    public function generar(TipoAtencion $tipo, int $atencionId): StreamedResponse;
}
