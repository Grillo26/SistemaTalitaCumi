<?php

namespace App\Modules\Pacientes\Queries\Contactos;

use App\Modules\Pacientes\Exceptions\ContactoEmergenciaNotFoundException;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Models\Paciente;

/**
 * Contacto de emergencia de un paciente (04.02/RF-012).
 */
final class GetContactoEmergenciaQuery
{
    public function __invoke(Paciente $paciente, int $contactoId): ContactoEmergencia
    {
        $contacto = ContactoEmergencia::query()
            ->where('paciente_id', $paciente->id)
            ->with('parentesco')
            ->find($contactoId);

        if ($contacto === null) {
            throw new ContactoEmergenciaNotFoundException($contactoId);
        }

        return $contacto;
    }
}
