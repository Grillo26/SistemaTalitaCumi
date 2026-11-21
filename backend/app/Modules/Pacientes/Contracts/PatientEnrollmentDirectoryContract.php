<?php

namespace App\Modules\Pacientes\Contracts;

/**
 * Inscripciones vigentes por paciente (módulo 06).
 *
 * 04.01 lo usará cuando exista 06 para ampliar visibilidad y filtros.
 */
interface PatientEnrollmentDirectoryContract
{
    /**
     * @return list<int>
     */
    public function sucursalesConInscripcionVigente(int $pacienteId): array;

    /**
     * @param  list<int>  $pacienteIds
     * @return array<int, list<int>>
     */
    public function sucursalesConInscripcionVigenteParaVarios(array $pacienteIds): array;
}
