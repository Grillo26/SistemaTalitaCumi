<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Exportación del catálogo (RF-C17).
 *
 * Pendiente de infraestructura: no hay dompdf/snappy ni maatwebsite/excel.
 */
interface CatalogoServiciosExporterContract
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarAreas(array $filtros): string;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarServicios(array $filtros): string;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarTarifas(array $filtros): string;

    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarCategorias(array $filtros): string;
}
