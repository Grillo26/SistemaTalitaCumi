<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Resumen de asistencia de una inscripción (RF-014).
 */
interface AsistenciaInscripcionContract
{
    public function disponible(): bool;

    public function resumen(int $inscripcionId): AsistenciaInscripcionResumen;
}
