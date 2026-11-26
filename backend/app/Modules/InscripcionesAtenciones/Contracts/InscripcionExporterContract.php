<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación del listado filtrado de inscripciones (RF-015).
 */
interface InscripcionExporterContract
{
    public function disponible(): bool;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportar(array $filtros, int $usuarioId): StreamedResponse;
}
