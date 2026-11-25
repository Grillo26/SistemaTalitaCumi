<?php

namespace App\Modules\Pacientes\Support;

use App\Modules\Pacientes\Models\ContactoEmergencia;

/**
 * Snapshot de contacto de emergencia para bitácora (04.02/RF-014).
 *
 * Guarda el teléfono completo; la consulta de bitácora exige `bitacora.ver`
 * y alcance por sucursal.
 */
final class ContactoEmergenciaSnapshot
{
    /**
     * @return array<string, mixed>
     */
    public static function from(ContactoEmergencia $contacto): array
    {
        $contacto->loadMissing('parentesco');

        return [
            'id' => (int) $contacto->id,
            'paciente_id' => (int) $contacto->paciente_id,
            'nombre_completo' => $contacto->nombre_completo,
            'parentesco_id' => $contacto->parentesco_id === null ? null : (int) $contacto->parentesco_id,
            'parentesco' => $contacto->parentesco?->nombre,
            'telefono' => $contacto->telefono,
            'version' => (int) $contacto->version,
            'updated_at' => $contacto->updated_at?->toIso8601String(),
        ];
    }
}
