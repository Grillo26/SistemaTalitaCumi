<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Exceptions\PacienteNoAgendableException;
use App\Modules\Pacientes\Exceptions\PacienteNoInscribibleException;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;

/**
 * Directorio de pacientes para 06/07/08/11 (04.01/FP-04, 04.01/RNF-011).
 *
 * Solo expone datos de identificación y estado. Nunca datos sensibles.
 */
interface PatientDirectoryContract
{
    public function existe(int $pacienteId): bool;

    /**
     * Nombres completos en lote para agendas y listados operativos.
     *
     * No expone fecha de nacimiento, edad, documento, diagnóstico ni
     * contacto. Incluye pacientes retirados para conservar turnos históricos.
     *
     * @param  list<int>  $pacienteIds
     * @return array<int, string>
     */
    public function nombres(array $pacienteIds): array;

    /**
     * @throws PacienteNotFoundException
     */
    public function resumen(int $pacienteId): PacienteResumen;

    /**
     * Solo pacientes activos pueden inscribirse.
     *
     * @throws PacienteNotFoundException
     * @throws PacienteNoInscribibleException
     */
    public function asegurarPermiteInscripcion(int $pacienteId): void;

    /**
     * Solo pacientes activos admiten turnos nuevos.
     *
     * @throws PacienteNotFoundException
     * @throws PacienteNoAgendableException
     */
    public function asegurarPermiteTurno(int $pacienteId): void;
}
