<?php

namespace App\Modules\Profesionales\Contracts;

/**
 * Exportación de directorio, matriz y asignaciones (03/RF-D15, 03/RF-E12, 03/RF-A15).
 *
 * Pendiente de infraestructura PDF/Excel. No se instalan librerías ni se
 * expone un endpoint o botón mientras no exista un exportador real.
 */
interface ProfessionalExporterContract
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarDirectorio(array $filtros): mixed;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarMatriz(array $filtros): mixed;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarAsignaciones(array $filtros): mixed;
}
