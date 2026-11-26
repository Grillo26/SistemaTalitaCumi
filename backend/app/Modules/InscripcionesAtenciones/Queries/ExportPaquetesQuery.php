<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\InscripcionExporterContract;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación de paquetes; reutiliza el contrato genérico de exportación.
 */
final class ExportPaquetesQuery
{
    public function __construct(private readonly InscripcionExporterContract $exporter) {}

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function __invoke(array $filtros, User $actor): StreamedResponse
    {
        return $this->exporter->exportar($filtros + ['tipo' => 'paquete_sesiones'], $actor->id);
    }
}
