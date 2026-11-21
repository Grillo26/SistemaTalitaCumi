<?php

namespace App\Modules\Profesionales\Contracts;

use LogicException;

/**
 * Stub de requisitos de diagnóstico mientras 05/06 no existan (03/Fase 3).
 */
final class StubProfessionalDiagnosticRequirementsContract implements ProfessionalDiagnosticRequirementsContract
{
    /**
     * @return list<array{especialidad_id: int, cantidad_minima: int}>
     */
    public function especialidadesRequeridas(int $tipoDiagnosticoId): array
    {
        throw new LogicException('Módulo 05/06 — Diagnósticos no implementado todavía.');
    }
}
