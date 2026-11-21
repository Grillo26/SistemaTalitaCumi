<?php

namespace App\Modules\Profesionales\Support;

use App\Models\User;
use App\Modules\Profesionales\Models\Especialidad;

/**
 * Habilidades de UI del catálogo de especialidades.
 */
final class EspecialidadAbilities
{
    /**
     * @return array{create: bool, administrar: bool, export: bool}
     */
    public static function collection(User $user): array
    {
        return [
            'create' => $user->can(ProfesionalesPermission::SPECIALTY_ADMIN),
            'administrar' => $user->can(ProfesionalesPermission::SPECIALTY_ADMIN),
            'export' => false,
        ];
    }

    /**
     * @return array{update: bool, change_status: bool, delete: bool}
     */
    public static function for(User $user, Especialidad $especialidad): array
    {
        return [
            'update' => $user->can(ProfesionalesPermission::SPECIALTY_ADMIN),
            'change_status' => $user->can(ProfesionalesPermission::SPECIALTY_ADMIN),
            'delete' => false,
        ];
    }
}
