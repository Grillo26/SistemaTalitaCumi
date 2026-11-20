<?php

namespace App\Modules\Organizacion\Support;

use App\Models\User;

/**
 * Habilidades que la UI puede mostrar para el usuario autenticado.
 *
 * La seguridad real se valida en el backend; estas banderas solo permiten
 * ocultar acciones que el usuario no puede ejecutar.
 */
final class BranchAbilities
{
    /**
     * @return array{create: bool, update: bool, update_contact: bool, change_status: bool, delete: bool}
     */
    public static function for(User $user): array
    {
        $canEditStructural = $user->can(BranchPermission::EDIT);
        $canEditContact = $user->can(BranchPermission::EDIT_CONTACT) || $canEditStructural;

        return [
            'create' => $user->can(BranchPermission::CREATE),
            'update' => $canEditStructural,
            'update_contact' => $canEditContact,
            'change_status' => $user->can(BranchPermission::CHANGE_STATUS),
            'delete' => false,
        ];
    }
}
