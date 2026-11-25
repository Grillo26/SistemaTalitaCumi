<?php

namespace App\Modules\Pacientes\Contracts;

use App\Models\User;
use LogicException;

/**
 * Exportador pendiente de infraestructura PDF (04.03/RF-010).
 */
final class UnavailableFichaIngresoExporter implements FichaIngresoExporterContract
{
    public function exportar(int $fichaId, User $actor): mixed
    {
        throw new LogicException('Exportación PDF de la ficha de ingreso pendiente de infraestructura.');
    }
}
