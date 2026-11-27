<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Estados Paciente e Inscripción] RF-012 / RNF-009.
 *
 * Exportación PDF/Excel pendiente de infraestructura. No genera archivos
 * vacíos ni simulados.
 */
interface EstadoExporterContract
{
    public function disponible(): bool;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportar(array $filtros, int $usuarioId): StreamedResponse;
}
