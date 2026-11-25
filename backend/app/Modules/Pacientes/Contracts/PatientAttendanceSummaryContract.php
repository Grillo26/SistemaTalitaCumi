<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Resumen de asistencia del período vigente (módulo 07).
 */
interface PatientAttendanceSummaryContract
{
    public function disponible(): bool;

    /**
     * @param  list<int>|null  $sucursalesAccesibles
     */
    public function resumenAsistencia(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
        ?int $sucursalContexto = null,
    ): AsistenciaResumen;
}
