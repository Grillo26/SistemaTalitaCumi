<?php

namespace App\Modules\Pacientes\Contracts;

use App\Models\User;
use LogicException;

/**
 * Stub de exportación PDF del listado mientras no exista infraestructura.
 */
final class UnavailablePacienteListadoPdfExporter implements PacienteListadoPdfExporterContract
{
    public function exportar(array $filtros, User $actor): mixed
    {
        throw new LogicException('Exportación PDF del listado de pacientes pendiente de infraestructura.');
    }
}
