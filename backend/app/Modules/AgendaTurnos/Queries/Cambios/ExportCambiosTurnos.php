<?php

namespace App\Modules\AgendaTurnos\Queries\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Contracts\CambiosTurnosExporterContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Reprogramación y Cancelación] RF-012 / RNF-010.
 */
final class ExportCambiosTurnos
{
    public function __construct(private readonly CambiosTurnosExporterContract $exporter) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, User $actor): StreamedResponse
    {
        return $this->exporter->exportar($filtros, (int) $actor->id);
    }
}
