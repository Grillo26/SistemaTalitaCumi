<?php

namespace App\Modules\Pacientes\Contracts;

use App\Models\User;

/**
 * Exportación PDF del listado de pacientes (04.04/RF-013).
 *
 * Pendiente de infraestructura. El CSV ya está implementado.
 */
interface PacienteListadoPdfExporterContract
{
    /**
     * @param  array<string, mixed>  $filtros  filtros seguros, sin datos sensibles
     */
    public function exportar(array $filtros, User $actor): mixed;
}
