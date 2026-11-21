<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Tutor;

/**
 * Habilidades de UI para tutores (04.02).
 */
final class TutorAbilities
{
    /**
     * @return array{create: bool, edit_data: bool, edit_contact: bool, change_status: bool, view_contact: bool, export: bool, delete: bool, manage_tutors: bool, view_patients: bool}
     */
    public static function collection(User $user): array
    {
        return [
            'create' => $user->can(PacientesPermission::TUTOR_CREATE),
            'edit_data' => $user->can(PacientesPermission::TUTOR_EDIT),
            'edit_contact' => $user->can(PacientesPermission::TUTOR_EDIT)
                || $user->can(PacientesPermission::TUTOR_EDIT_CONTACT),
            'change_status' => $user->can(PacientesPermission::TUTOR_CHANGE_STATUS),
            'view_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'export' => $user->can(PacientesPermission::TUTOR_EXPORT),
            'delete' => false,
            'manage_tutors' => $user->can(PacientesPermission::TUTOR_ASSOCIATE),
            'view_patients' => $user->can(PacientesPermission::PATIENT_VIEW),
        ];
    }

    /**
     * @return array{edit_data: bool, edit_contact: bool, change_status: bool, view_contact: bool, delete: bool, manage_tutors: bool, view_patients: bool}
     */
    public static function for(User $user, Tutor $tutor, bool $identityEditable = true): array
    {
        return [
            'edit_data' => $identityEditable && $user->can(PacientesPermission::TUTOR_EDIT),
            'edit_contact' => $user->can(PacientesPermission::TUTOR_EDIT)
                || $user->can(PacientesPermission::TUTOR_EDIT_CONTACT),
            'change_status' => $user->can(PacientesPermission::TUTOR_CHANGE_STATUS),
            'view_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'delete' => false,
            'manage_tutors' => $user->can(PacientesPermission::TUTOR_ASSOCIATE),
            'view_patients' => $user->can(PacientesPermission::PATIENT_VIEW),
        ];
    }
}
