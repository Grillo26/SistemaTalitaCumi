<?php

namespace App\Modules\Pacientes\Queries\Contactos;

use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contactos de emergencia de un paciente (04.02/RF-012).
 */
final class ListContactosEmergenciaQuery
{
    /**
     * @return Collection<int, ContactoEmergencia>
     */
    public function __invoke(Paciente $paciente): Collection
    {
        return ContactoEmergencia::query()
            ->where('paciente_id', $paciente->id)
            ->with('parentesco')
            ->orderBy('nombre_normalizado')
            ->orderBy('id')
            ->get();
    }
}
