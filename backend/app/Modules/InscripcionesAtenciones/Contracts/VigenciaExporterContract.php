<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Vigencia y Renovación] RF-010 / CA-010.
 *
 * Infraestructura PDF/Excel pendiente. El stub no genera archivos falsos.
 */
interface VigenciaExporterContract
{
    public function disponible(): bool;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportar(array $filtros, int $usuarioId): StreamedResponse;
}
