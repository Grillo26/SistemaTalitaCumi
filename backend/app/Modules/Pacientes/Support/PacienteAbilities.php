<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Paciente;

/**
 * Habilidades de UI del módulo 04.
 *
 * Solo ocultan acciones; el backend siempre revalida.
 */
final class PacienteAbilities
{
    /**
     * @return array{create: bool, edit: bool, change_status: bool, view_sensibles: bool, export: bool, tutors: bool, diagnostics: bool}
     */
    public static function collection(User $user): array
    {
        return [
            'create' => $user->can(PacientesPermission::PATIENT_CREATE),
            'edit' => $user->can(PacientesPermission::PATIENT_EDIT),
            'change_status' => $user->can(PacientesPermission::PATIENT_CHANGE_STATUS),
            'view_sensibles' => $user->can(PacientesPermission::PATIENT_VIEW_SENSITIVE),
            'export' => false,
            'tutors' => PacientesModule::TUTORES_DISPONIBLES,
            'diagnostics' => PacientesModule::DIAGNOSTICOS_DISPONIBLES,
        ];
    }

    /**
     * @return array{edit: bool, change_status: bool, view_sensibles: bool, delete: bool, view_tutors: bool}
     */
    public static function for(User $user, Paciente $paciente): array
    {
        return [
            'edit' => $user->can(PacientesPermission::PATIENT_EDIT),
            'change_status' => $user->can(PacientesPermission::PATIENT_CHANGE_STATUS),
            'view_sensibles' => $user->can(PacientesPermission::PATIENT_VIEW_SENSITIVE),
            'delete' => false,
            'view_tutors' => PacientesModule::TUTORES_DISPONIBLES,
        ];
    }
}
