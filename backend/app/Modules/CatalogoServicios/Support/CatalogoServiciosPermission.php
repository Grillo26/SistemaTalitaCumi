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

    public const TARIFA_VIEW = 'tarifas.ver';

    public const TARIFA_EDIT = 'tarifas.editar';

    public const CATEGORIA_VIEW = 'categorias.ver';

    public const CATEGORIA_EDIT = 'categorias.editar';

    /**
     * Permisos del submódulo 01.
     *
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

    /**
     * Permisos del submódulo 02 (tarifas y categorías).
     *
     * @return list<string>
     */
    public static function tarifas(): array
    {
        return [
            self::TARIFA_VIEW,
            self::TARIFA_EDIT,
            self::CATEGORIA_VIEW,
            self::CATEGORIA_EDIT,
        ];
    }
}
