<?php

namespace App\Modules\Profesionales\Contracts;

/**
 * Especialidades requeridas por tipo de diagnóstico (03/RF-A07, 03/RF-A08).
 *
 * El origen real es el catálogo del módulo 05
 * (`tipo_diagnostico_profesional`: especialidad y cantidad mínima). La
 * asignación efectiva de participantes pertenece a 06/07.
 */
interface ProfessionalDiagnosticRequirementsContract
{
    /**
     * @return list<array{especialidad_id: int, cantidad_minima: int}>
     */
    public function especialidadesRequeridas(int $tipoDiagnosticoId): array;
}
