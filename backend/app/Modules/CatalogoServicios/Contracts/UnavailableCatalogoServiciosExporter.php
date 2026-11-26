<?php

namespace App\Modules\CatalogoServicios\Contracts;

use LogicException;

/**
 * Implementación temporal de exportación (RF-C17, prioridad baja).
 */
final class UnavailableCatalogoServiciosExporter implements CatalogoServiciosExporterContract
{
    public function exportarAreas(array $filtros): string
    {
        throw $this->notImplemented();
    }

    public function exportarServicios(array $filtros): string
    {
        throw $this->notImplemented();
    }

    private function notImplemented(): LogicException
    {
        return new LogicException(
            'Módulo 05 — Exportación PDF/Excel pendiente de infraestructura.',
        );
    }
}
