<?php

namespace App\Modules\Organizacion\Support;

use App\Models\User;

/**
 * Banderas `can` del submódulo de salas y ambientes para la UI.
 */
final class SalasAmbientesAbilities
{
    /**
     * @return array<string, mixed>
     */
    public static function for(User $user): array
    {
        $tipoSalaAdmin = $user->can(SalasAmbientesPermission::TIPO_SALA_ADMIN);
        $turnoAdmin = $user->can(SalasAmbientesPermission::TURNO_ADMIN);

        $salaStructure = $user->can(SalasAmbientesPermission::SALA_EDIT_STRUCTURE);
        $salaData = $user->can(SalasAmbientesPermission::SALA_EDIT_DATA) || $salaStructure;

        $ambienteStructure = $user->can(SalasAmbientesPermission::AMBIENTE_EDIT_STRUCTURE);
        $ambienteData = $user->can(SalasAmbientesPermission::AMBIENTE_EDIT_DATA) || $ambienteStructure;

        return [
            'global_scope' => $user->can(BranchPermission::GLOBAL_SCOPE),
            'catalogos' => [
                'view' => $user->can(SalasAmbientesPermission::CATALOG_VIEW),
                'tipos_sala' => [
                    'create' => $tipoSalaAdmin,
                    'update' => $tipoSalaAdmin,
                    'change_status' => $tipoSalaAdmin,
                ],
                'turnos' => [
                    'create' => $turnoAdmin,
                    'update' => $turnoAdmin,
                    'change_status' => $turnoAdmin,
                ],
            ],
            'salas' => [
                'create' => $user->can(SalasAmbientesPermission::SALA_CREATE),
                'update_structure' => $salaStructure,
                'update_data' => $salaData,
                'change_status' => $user->can(SalasAmbientesPermission::SALA_CHANGE_STATUS),
                'delete' => false,
            ],
            'ambientes' => [
                'create' => $user->can(SalasAmbientesPermission::AMBIENTE_CREATE),
                'update_structure' => $ambienteStructure,
                'update_data' => $ambienteData,
                'change_status' => $user->can(SalasAmbientesPermission::AMBIENTE_CHANGE_STATUS),
                'delete' => false,
            ],
            // RF-016 pendiente de infraestructura: aunque exista el permiso,
            // la exportación no se expone todavía.
            'export' => false,
        ];
    }
}
