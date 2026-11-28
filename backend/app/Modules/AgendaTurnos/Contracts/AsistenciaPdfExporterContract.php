<?php

namespace App\Modules\AgendaTurnos\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 07.04/RF-010 / RNF-010 — PDF pendiente de infraestructura.
 */
interface AsistenciaPdfExporterContract
{
    public function disponible(): bool;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportar(array $filtros, int $usuarioId): StreamedResponse;
}
