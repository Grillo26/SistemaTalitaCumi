<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\FichaIngreso;

/**
 * Sucursales de bitácora de la ficha (04.03/RF-011).
 */
final class FichaIngresoBranchResolver
{
    /**
     * @return list<int>
     */
    public function for(FichaIngreso $ficha): array
    {
        $ficha->loadMissing('paciente');

        return array_values(array_unique([
            (int) $ficha->sucursal_ingreso_id,
            (int) $ficha->paciente->sucursal_registro_id,
        ]));
    }
}
