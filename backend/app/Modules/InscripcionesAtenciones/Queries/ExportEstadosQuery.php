<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\EstadoExporterContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Estados Paciente e Inscripción] RF-012 / CA-010.
 */
final class ExportEstadosQuery
{
    public function __construct(private readonly EstadoExporterContract $exporter) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, User $actor): StreamedResponse
    {
        return $this->exporter->exportar($filtros, $actor->id);
    }
}
