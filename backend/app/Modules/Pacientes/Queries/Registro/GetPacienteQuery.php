<?php

namespace App\Modules\Pacientes\Queries\Registro;

use App\Models\User;
use App\Modules\Pacientes\Exceptions\PacienteNotFoundException;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Support\PacienteAccessScope;

/**
 * Ficha de paciente (04.01/RF-006).
 */
final class GetPacienteQuery
{
    public function __construct(private readonly PacienteAccessScope $scope) {}

    public function __invoke(int $pacienteId, User $actor): Paciente
    {
        $paciente = Paciente::query()
            ->with(['sucursalRegistro', 'relacionesTutor.tutor'])
            ->find($pacienteId);

        if ($paciente === null) {
            throw new PacienteNotFoundException($pacienteId);
        }

        $this->scope->assertCanAccess($actor, $paciente);

        return $paciente;
    }
}
