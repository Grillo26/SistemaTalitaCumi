<?php

namespace App\Modules\CatalogoServicios\Support;

use App\Modules\Organizacion\Support\BranchPermission;

/**
 * Banderas `can` del módulo 05 para la UI.
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
        $tarifaEdit = $user->can(CatalogoServiciosPermission::TARIFA_EDIT);
        $categoriaEdit = $user->can(CatalogoServiciosPermission::CATEGORIA_EDIT);
        $diagnosticoEdit = $user->can(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_EDIT);

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
            'tarifas' => [
                'view' => $user->can(CatalogoServiciosPermission::TARIFA_VIEW),
                'create' => $tarifaEdit,
                'update' => $tarifaEdit,
                'close' => $tarifaEdit,
                'delete' => false,
                'global_scope' => $user->can(BranchPermission::GLOBAL_SCOPE),
            ],
            'categorias' => [
                'view' => $user->can(CatalogoServiciosPermission::CATEGORIA_VIEW),
                'create' => $categoriaEdit,
                'update' => $categoriaEdit,
                'change_status' => $categoriaEdit,
                'delete' => false,
            ],
            'tipos_diagnostico' => [
                'view' => $user->can(CatalogoServiciosPermission::TIPO_DIAGNOSTICO_VIEW),
                'create' => $diagnosticoEdit,
                'update' => $diagnosticoEdit,
                'change_status' => $diagnosticoEdit,
                'delete' => false,
            ],
            // RF-C17, RF-T18 y RF-D13 pendientes de infraestructura.
            'export' => false,
        ];
    }
}
