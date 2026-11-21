<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Tutor;

/**
 * Habilidades de UI para tutores (04.02 núcleo).
 */
final class TutorAbilities
{
    /**
     * @return array{create: bool, edit: bool, change_status: bool, export: bool}
     */
    public static function collection(User $user): array
    {
        return [
            'create' => $user->can(PacientesPermission::TUTOR_CREATE),
            'edit' => $user->can(PacientesPermission::TUTOR_EDIT),
            'change_status' => $user->can(PacientesPermission::TUTOR_CHANGE_STATUS),
            'export' => false,
        ];
    }

    /**
     * @return array{edit: bool, change_status: bool}
     */
    public static function for(User $user, Tutor $tutor): array
    {
        return [
            'edit' => $user->can(PacientesPermission::TUTOR_EDIT),
            'change_status' => $user->can(PacientesPermission::TUTOR_CHANGE_STATUS),
        ];
    }
}
