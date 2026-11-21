<?php

namespace App\Modules\Organizacion\Support;

use App\Models\User;

/**
 * Banderas `can` del submódulo de configuración para la UI.
 */
final class ConfiguracionAbilities
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $user): array
    {
        $viewAll = $user->can(ConfiguracionPermission::VIEW);
        $viewOperatives = $user->can(ConfiguracionPermission::VIEW_OPERATIVES);

        return [
            'view' => $viewAll || $viewOperatives,
            'view_all' => $viewAll,
            'view_operatives' => $viewOperatives,
            'update' => $user->can(ConfiguracionPermission::EDIT),
            'update_logo' => $user->can(ConfiguracionPermission::EDIT),
            'restore' => $user->can(ConfiguracionPermission::RESTORE),
            // RF-015 pendiente de infraestructura: el permiso existe, pero la
            // exportación no se expone todavía.
            'export' => false,
        ];
    }
}
