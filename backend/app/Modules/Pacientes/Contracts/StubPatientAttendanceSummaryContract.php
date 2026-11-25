<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub de asistencia mientras no exista el módulo 07.
 */
final class StubPatientAttendanceSummaryContract implements PatientAttendanceSummaryContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function resumenAsistencia(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
        ?int $sucursalContexto = null,
    ): AsistenciaResumen {
        throw new LogicException('Módulo 07 — Agenda y asistencia no implementado todavía.');
    }
}
