<?php

namespace App\Modules\Pacientes\Http\Resources;

use App\Models\User;
use App\Modules\Pacientes\Models\ContactoEmergencia;
use App\Modules\Pacientes\Support\PacientesPermission;
use App\Modules\Pacientes\Support\PhoneMask;
use Illuminate\Http\Request;

/**
 * Contacto de emergencia (04.02/RF-012).
 */
final class ContactoEmergenciaResource
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $actor, ContactoEmergencia $contacto, Request $request): array
    {
        $contacto->loadMissing('parentesco');
        $fullPhone = $actor->can(PacientesPermission::CONTACT_EMERGENCY_VIEW);

        return [
            'id' => (int) $contacto->id,
            'paciente_id' => (int) $contacto->paciente_id,
            'nombre_completo' => $contacto->nombre_completo,
            'parentesco_id' => $contacto->parentesco_id === null ? null : (int) $contacto->parentesco_id,
            'parentesco' => $contacto->parentesco?->nombre,
            'telefono' => $fullPhone ? $contacto->telefono : PhoneMask::mask($contacto->telefono),
            'version' => (int) $contacto->version,
            'created_at' => $contacto->created_at?->toIso8601String(),
            'updated_at' => $contacto->updated_at?->toIso8601String(),
        ];
    }
}
