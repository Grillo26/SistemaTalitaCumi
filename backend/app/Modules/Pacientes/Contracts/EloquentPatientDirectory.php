<?php

namespace App\Modules\Pacientes\Contracts;

use App\Modules\Pacientes\Exceptions\PacienteNoAgendableException;
use App\Modules\Pacientes\Exceptions\PacienteNoInscribibleException;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\PacienteOperationalStateRule;
use App\Modules\Pacientes\Support\PacienteAgeCalculator;

/**
 * Implementación Eloquent del directorio de pacientes.
 */
final class EloquentPatientDirectory implements PatientDirectoryContract
{
    public function __construct(
        private readonly PacienteAgeCalculator $age,
        private readonly PacienteOperationalStateRule $stateRule,
    ) {}

    public function existe(int $pacienteId): bool
    {
        return Paciente::query()->whereKey($pacienteId)->exists();
    }

    public function resumen(int $pacienteId): PacienteResumen
    {
        $paciente = $this->find($pacienteId);
        $edad = $this->age->for($paciente);

        return new PacienteResumen(
            id: (int) $paciente->id,
            codigo: $paciente->codigo,
            nombreCompleto: $paciente->nombre_completo,
            fechaNacimiento: $paciente->fecha_nacimiento->toDateString(),
            edadAnios: $edad['anios'],
            estado: $paciente->estado,
            sucursalRegistroId: (int) $paciente->sucursal_registro_id,
            tipoDiagnosticoId: $paciente->tipo_diagnostico_id === null ? null : (int) $paciente->tipo_diagnostico_id,
            sinDiagnosticoDefinido: $paciente->tipo_diagnostico_id === null,
        );
    }

    public function asegurarPermiteInscripcion(int $pacienteId): void
    {
        $paciente = $this->find($pacienteId);

        if (! $this->stateRule->permiteInscripcion($paciente->estado)) {
            throw new PacienteNoInscribibleException($paciente->estado);
        }
    }

    public function asegurarPermiteTurno(int $pacienteId): void
    {
        $paciente = $this->find($pacienteId);

        if (! $this->stateRule->permiteTurnoNuevo($paciente->estado)) {
            throw new PacienteNoAgendableException($paciente->estado);
        }
    }

    private function find(int $pacienteId): Paciente
    {
        $paciente = Paciente::query()->find($pacienteId);

        if ($paciente === null) {
            throw new PacienteNotFoundException($pacienteId);
        }

        return $paciente;
    }
}
