<?php

namespace App\Modules\Profesionales\Contracts;

use LogicException;

/**
 * Exportador no disponible: infraestructura PDF/Excel pendiente (03/Fase 3).
 */
final class UnavailableProfessionalExporter implements ProfessionalExporterContract
{
    public function exportarDirectorio(array $filtros): mixed
    {
        throw new LogicException('Exportación PDF/Excel pendiente de infraestructura.');
    }

    public function exportarMatriz(array $filtros): mixed
    {
        throw new LogicException('Exportación PDF/Excel pendiente de infraestructura.');
    }

    public function exportarAsignaciones(array $filtros): mixed
    {
        throw new LogicException('Exportación PDF/Excel pendiente de infraestructura.');
    }
}
