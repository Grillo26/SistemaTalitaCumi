<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub de inscripciones (módulo 06).
 *
 * No se invoca en listado ni ficha mientras `INSCRIPCIONES_DISPONIBLES=false`.
 */
final class StubPatientEnrollmentDirectoryContract implements PatientEnrollmentDirectoryContract
{
    public function sucursalesConInscripcionVigente(int $pacienteId): array
    {
        throw $this->unavailable();
    }

    public function sucursalesConInscripcionVigenteParaVarios(array $pacienteIds): array
    {
        throw $this->unavailable();
    }

    private function unavailable(): LogicException
    {
        return new LogicException('Módulo 06 — Inscripciones no implementado todavía.');
    }
}
