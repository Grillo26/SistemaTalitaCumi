<?php

namespace App\Modules\AgendaTurnos\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 07.05/RF-008 / 07.05/RNF-010 — PDF pendiente de infraestructura.
 */
interface DiagnosticosPdfExporterContract
{
    public function disponible(): bool;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportar(array $filtros, int $usuarioId): StreamedResponse;
}
