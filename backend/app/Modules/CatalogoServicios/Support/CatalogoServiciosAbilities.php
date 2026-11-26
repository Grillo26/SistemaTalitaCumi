<?php

namespace App\Modules\CatalogoServicios\Support;

/**
 * Banderas `can` del submódulo 01 para la UI.
 *
 * La seguridad real la valida Spatie en cada ruta; esto solo oculta
 * acciones no permitidas.
 */
final class CatalogoServiciosAbilities
{
    /**
     * @return array<string, mixed>
     */
    public static function for(\App\Models\User $user): array
    {
        $areaEdit = $user->can(CatalogoServiciosPermission::AREA_EDIT);
        $servicioEdit = $user->can(CatalogoServiciosPermission::SERVICIO_EDIT);

        return [
            'areas' => [
                'view' => $user->can(CatalogoServiciosPermission::AREA_VIEW),
                'create' => $areaEdit,
                'update' => $areaEdit,
                'change_status' => $areaEdit,
                'delete' => false,
            ],
            'servicios' => [
                'view' => $user->can(CatalogoServiciosPermission::SERVICIO_VIEW),
                'create' => $servicioEdit,
                'update' => $servicioEdit,
                'change_status' => $servicioEdit,
                'delete' => false,
            ],
            'tipos_servicio' => [
                'view' => $user->can(CatalogoServiciosPermission::SERVICIO_VIEW),
                'create' => $servicioEdit,
                'update' => $servicioEdit,
                'change_status' => $servicioEdit,
                'delete' => false,
            ],
            // RF-C17 pendiente de infraestructura: no se expone exportación.
            'export' => false,
        ];
    }
}
