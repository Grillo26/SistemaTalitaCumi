<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\VigenciaExporterContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Vigencia y Renovación] RF-010 / CA-010.
 *
 * No genera archivos falsos: si falta infraestructura, el contrato responde
 * 503 con mensaje claro.
 */
final class ExportVigenciasQuery
{
    public function __construct(private readonly VigenciaExporterContract $exporter) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, User $actor): StreamedResponse
    {
        return $this->exporter->exportar($filtros, $actor->id);
    }
}
