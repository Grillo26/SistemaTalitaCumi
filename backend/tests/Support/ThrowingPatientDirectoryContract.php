<?php

namespace Tests\Support;

use App\Modules\Pacientes\Contracts\PacienteResumen;
use App\Modules\Pacientes\Contracts\PatientDirectoryContract;
use LogicException;

final class ThrowingPatientDirectoryContract implements PatientDirectoryContract
{
    public function existe(int $pacienteId): bool
    {
        throw $this->error();
    }

    public function nombres(array $pacienteIds): array
    {
        throw $this->error();
    }

    public function resumen(int $pacienteId): PacienteResumen
    {
        throw $this->error();
    }

    public function asegurarPermiteInscripcion(int $pacienteId): void
    {
        throw $this->error();
    }

    public function asegurarPermiteTurno(int $pacienteId): void
    {
        throw $this->error();
    }

    private function error(): LogicException
    {
        return new LogicException('Módulo Pacientes no disponible en este test.');
    }
}
