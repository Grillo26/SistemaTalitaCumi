<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub de servicios vigentes mientras no exista el módulo 06.
 */
final class StubPatientServicesSummaryContract implements PatientServicesSummaryContract
{
    public function disponible(): bool
    {
        return false;
    }

    public function serviciosVigentes(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
        ?int $sucursalContexto = null,
    ): array {
        throw $this->unavailable();
    }

    public function sucursalesConServicios(
        int $pacienteId,
        ?array $sucursalesAccesibles = null,
    ): array {
        throw $this->unavailable();
    }

    private function unavailable(): LogicException
    {
        return new LogicException('Módulo 06 — Inscripciones y servicios no implementado todavía.');
    }
}
