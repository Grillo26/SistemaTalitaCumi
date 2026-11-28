<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Página de diagnósticos para el listado y reporte de 07.05.
 */
final readonly class DiagnosticoAtencionPagina
{
    /**
     * @param  list<DiagnosticoAtencionResumen>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
        public int $lastPage,
    ) {}
}
