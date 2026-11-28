<?php

namespace App\Modules\AgendaTurnos\Contracts;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Exportación de la agenda semanal filtrada (RF-010 / RN-008).
 *
 * La implementación real necesita infraestructura PDF. Mientras no exista,
 * el binding devuelve `UnavailableAgendaExporter` y el endpoint responde 503.
 */
interface AgendaExporterContract
{
    public function disponible(): bool;

    /**
     * @param  array<string, mixed>  $datos
     */
    public function exportar(array $datos, int $usuarioId): StreamedResponse;
}
