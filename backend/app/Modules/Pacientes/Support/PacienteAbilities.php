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
     * @return array{create: bool, edit: bool, change_status: bool, view_sensibles: bool, export: bool, export_listado: bool, view_resumen: bool, search_by_tutor: bool, search_by_tutor_phone: bool, alerta_pago: bool, view_ficha_ingreso: bool, view_contact: bool, tutors: bool, diagnostics: bool, manage_tutors: bool, view_emergency_contacts: bool, manage_emergency_contacts: bool}
     */
    public static function collection(User $user): array
    {
        return [
            'create' => $user->can(PacientesPermission::PATIENT_CREATE),
            'edit' => $user->can(PacientesPermission::PATIENT_EDIT),
            'change_status' => $user->can(PacientesPermission::PATIENT_CHANGE_STATUS)
                || $user->can(PacientesPermission::PATIENT_RETIRE),
            'view_sensibles' => $user->can(PacientesPermission::PATIENT_VIEW_SENSITIVE),
            'export' => false,
            'export_listado' => $user->can(PacienteResumenPermission::EXPORT_LISTADO),
            'view_resumen' => $user->can(PacientesPermission::PATIENT_VIEW),
            'search_by_tutor' => $user->can(PacientesPermission::PATIENT_VIEW),
            'search_by_tutor_phone' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'alerta_pago' => false,
            'view_ficha_ingreso' => $user->can(FichaIngresoPermission::VIEW),
            'view_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'tutors' => PacientesModule::TUTORES_DISPONIBLES,
            'diagnostics' => PacientesModule::DIAGNOSTICOS_DISPONIBLES,
            'manage_tutors' => $user->can(PacientesPermission::TUTOR_ASSOCIATE),
            'view_emergency_contacts' => PacientesModule::CONTACTOS_EMERGENCIA_DISPONIBLES
                && $user->can(PacientesPermission::CONTACT_EMERGENCY_VIEW),
            'manage_emergency_contacts' => PacientesModule::CONTACTOS_EMERGENCIA_DISPONIBLES
                && $user->can(PacientesPermission::CONTACT_EMERGENCY_EDIT),
        ];
    }

    /**
     * @return array{edit: bool, change_status: bool, view_sensibles: bool, delete: bool, view_resumen: bool, view_ficha_ingreso: bool, view_contact: bool, view_tutors: bool, manage_tutors: bool, view_emergency_contacts: bool, manage_emergency_contacts: bool}
     */
    public static function for(User $user, Paciente $paciente): array
    {
        return [
            'edit' => $user->can(PacientesPermission::PATIENT_EDIT),
            'change_status' => $user->can(PacientesPermission::PATIENT_CHANGE_STATUS)
                || $user->can(PacientesPermission::PATIENT_RETIRE),
            'view_sensibles' => $user->can(PacientesPermission::PATIENT_VIEW_SENSITIVE),
            'delete' => false,
            'view_resumen' => $user->can(PacientesPermission::PATIENT_VIEW),
            'view_ficha_ingreso' => $user->can(FichaIngresoPermission::VIEW),
            'view_contact' => $user->can(PacientesPermission::TUTOR_VIEW_CONTACT),
            'view_tutors' => PacientesModule::TUTORES_DISPONIBLES,
            'manage_tutors' => $user->can(PacientesPermission::TUTOR_ASSOCIATE),
            'view_emergency_contacts' => PacientesModule::CONTACTOS_EMERGENCIA_DISPONIBLES
                && $user->can(PacientesPermission::CONTACT_EMERGENCY_VIEW),
            'manage_emergency_contacts' => PacientesModule::CONTACTOS_EMERGENCIA_DISPONIBLES
                && $user->can(PacientesPermission::CONTACT_EMERGENCY_EDIT),
        ];
    }
}
