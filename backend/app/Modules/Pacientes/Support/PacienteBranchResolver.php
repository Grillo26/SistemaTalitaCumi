<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\Paciente;

/**
 * Sucursales de bitácora/visibilidad del paciente.
 *
 * Fase 1: solo la sucursal de registro. Cuando exista 06 se sumarán las
 * sucursales con inscripción vigente mediante un contrato/proyección.
 */
final class PacienteBranchResolver
{
    /**
     * @return list<int>
     */
    public function for(Paciente $paciente): array
    {
        return [(int) $paciente->sucursal_registro_id];
    }
}
