<?php

namespace App\Modules\AgendaTurnos\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Reprogramación y Cancelación] RF-012 / RNF-010.
 *
 * Exportación PDF/Excel pendiente de infraestructura. No se generan archivos
 * vacíos ni simulados.
 */
interface CambiosTurnosExporterContract
{
    public function disponible(): bool;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportar(array $filtros, int $usuarioId): StreamedResponse;
}
