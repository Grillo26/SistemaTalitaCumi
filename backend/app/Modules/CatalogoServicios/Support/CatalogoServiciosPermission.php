<?php

namespace App\Modules\CatalogoServicios\Support;

/**
 * Permisos del submódulo 01 (RF-C16).
 *
 * Se usan los códigos de la spec. No se decide por nombre de rol.
 */
final class CatalogoServiciosPermission
{
    public const AREA_VIEW = 'areas.ver';

    public const AREA_EDIT = 'areas.editar';

    public const SERVICIO_VIEW = 'servicios.ver';

    public const SERVICIO_EDIT = 'servicios.editar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::AREA_VIEW,
            self::AREA_EDIT,
            self::SERVICIO_VIEW,
            self::SERVICIO_EDIT,
        ];
    }
}
