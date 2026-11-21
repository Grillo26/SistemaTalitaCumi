<?php

namespace App\Modules\Pacientes\Contracts;

use App\Models\User;

/**
 * Exportación de la ficha de ingreso (04.03/RF-010).
 */
interface FichaIngresoExporterContract
{
    public function exportar(int $fichaId, User $actor): mixed;
}
