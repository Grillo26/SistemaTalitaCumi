<?php

namespace App\Modules\CatalogoServicios\Contracts;

/**
 * Uso histórico de un tipo de diagnóstico (RF-D09 / RN-D07).
 *
 * Se implementará cuando existan diagnósticos registrados (06/11).
 */
interface TipoDiagnosticoUsageContract
{
    public function hasDiagnosticosRegistrados(int $tipoDiagnosticoId): bool;
}
