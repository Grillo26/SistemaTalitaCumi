<?php

namespace App\Modules\Profesionales\Support;

use App\Models\User;
use App\Modules\Profesionales\Models\Profesional;

/**
 * Habilidades de UI para el módulo 03.
 *
 * Solo ocultan acciones; el backend siempre revalida.
 */
final class ProfesionalAbilities
{
    /**
     * @return array{create: bool, export: bool, view_matrix: bool, manage_branches: bool, secundarias: bool}
     */
    public static function collection(User $user): array
    {
        return [
            'create' => $user->can(ProfesionalesPermission::PROFESSIONAL_CREATE),
            'export' => false,
            'view_matrix' => $user->can(ProfesionalesPermission::PROFESSIONAL_VIEW_MATRIX),
            'manage_branches' => $user->can(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN),
            'secundarias' => ProfesionalesModule::SECUNDARIAS_HABILITADAS,
        ];
    }

    /**
     * @return array{update_data: bool, update_contact: bool, change_status: bool, delete: bool, manage_branches: bool}
     */
    public static function for(User $user, Profesional $profesional): array
    {
        return [
            'update_data' => $user->can(ProfesionalesPermission::PROFESSIONAL_EDIT_DATA),
            'update_contact' => $user->can(ProfesionalesPermission::PROFESSIONAL_EDIT_CONTACT)
                || $user->can(ProfesionalesPermission::PROFESSIONAL_EDIT_DATA),
            'change_status' => $user->can(ProfesionalesPermission::PROFESSIONAL_CHANGE_STATUS),
            'delete' => false,
            'manage_branches' => $user->can(ProfesionalesPermission::PROFESSIONAL_BRANCH_ADMIN),
        ];
    }
}
