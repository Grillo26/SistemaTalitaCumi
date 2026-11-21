<?php

namespace App\Modules\Pacientes\Contracts;

use App\Models\User;
use LogicException;

/**
 * Exportador no disponible: PDF/Excel pendiente de infraestructura.
 */
final class UnavailablePacienteExporter implements PacienteExporterContract
{
    public function exportarListado(array $filtros, User $actor): mixed
    {
        throw $this->unavailable();
    }

    public function exportarFicha(int $pacienteId, User $actor): mixed
    {
        throw $this->unavailable();
    }

    private function unavailable(): LogicException
    {
        return new LogicException('Exportación PDF/Excel pendiente de infraestructura.');
    }
}
