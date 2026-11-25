<?php

namespace App\Modules\Pacientes\Contracts;

use LogicException;

/**
 * Stub del catálogo de diagnósticos (módulo 05).
 *
 * No se invoca en flujos normales mientras `DIAGNOSTICOS_DISPONIBLES=false`.
 */
final class StubDiagnosticTypeDirectoryContract implements DiagnosticTypeDirectoryContract
{
    public function existe(int $tipoDiagnosticoId): bool
    {
        throw $this->unavailable();
    }

    public function estaActivo(int $tipoDiagnosticoId): bool
    {
        throw $this->unavailable();
    }

    public function asegurarActivo(int $tipoDiagnosticoId): void
    {
        throw $this->unavailable();
    }

    public function nombre(int $tipoDiagnosticoId): string
    {
        throw $this->unavailable();
    }

    private function unavailable(): LogicException
    {
        return new LogicException('Módulo 05 — Catálogo de tipos de diagnóstico no implementado todavía.');
    }
}
