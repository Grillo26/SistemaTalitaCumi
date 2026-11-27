<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * PDF del comprobante de un paquete (RF-015).
 *
 * La infraestructura PDF no está instalada; la vista imprimible HTML es la
 * alternativa operativa.
 */
interface ComprobantePaquetePdfExporterContract
{
    public function disponible(): bool;

    public function generar(int $paqueteId): StreamedResponse;
}
