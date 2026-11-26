<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\InscripcionExporterContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación del listado (RF-015). Pendiente de infraestructura PDF/Excel.
 */
final class ExportInscripcionesQuery
{
    public function __construct(private readonly InscripcionExporterContract $exporter) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, User $actor): StreamedResponse
    {
        return $this->exporter->exportar($filtros, $actor->id);
    }
}
