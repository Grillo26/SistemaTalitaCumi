<?php

namespace App\Modules\Organizacion\Support;

/**
 * Códigos de permiso del submódulo de sucursales.
 *
 * La aplicación nunca decide por nombre de rol: solo por permisos.
 */
final class BranchPermission
{
    public const VIEW = 'sucursal.ver';

    public const CREATE = 'sucursal.crear';

    /** Edición estructural: código y nombre. */
    public const EDIT = 'sucursal.editar';

    /** Edición de contacto y dirección. */
    public const EDIT_CONTACT = 'sucursal.editar_contacto';

    /** Activar y desactivar. */
    public const CHANGE_STATUS = 'sucursal.cambiar_estado';

    /** Alcance global: ver todas las sucursales sin depender de usuario_sucursal. */
    public const GLOBAL_SCOPE = 'sucursal.alcance_global';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::EDIT_CONTACT,
            self::CHANGE_STATUS,
            self::GLOBAL_SCOPE,
        ];
    }
}
