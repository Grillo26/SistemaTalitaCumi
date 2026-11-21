<?php

namespace App\Modules\Pacientes\Contracts;

use App\Models\User;

/**
 * Exportación de pacientes (04.01/RF-015).
 */
interface PacienteExporterContract
{
    /**
     * @param  array<string, mixed>  $filtros
     */
    public function exportarListado(array $filtros, User $actor): mixed;

    public function exportarFicha(int $pacienteId, User $actor): mixed;
}
