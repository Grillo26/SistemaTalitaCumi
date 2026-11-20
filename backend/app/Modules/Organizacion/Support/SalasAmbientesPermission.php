<?php

namespace App\Modules\Organizacion\Support;

/**
 * Códigos de permiso del submódulo de salas y ambientes.
 *
 * La aplicación decide por permiso, nunca por nombre de rol.
 */
final class SalasAmbientesPermission
{
    public const CATALOG_VIEW = 'catalogo.ver';

    public const TIPO_SALA_ADMIN = 'tipo_sala.administrar';

    public const TURNO_ADMIN = 'turno.administrar';

    public const SALA_VIEW = 'sala.ver';

    public const SALA_CREATE = 'sala.crear';

    public const SALA_EDIT_STRUCTURE = 'sala.editar_estructura';

    public const SALA_EDIT_DATA = 'sala.editar_datos';

    public const SALA_CHANGE_STATUS = 'sala.cambiar_estado';

    public const AMBIENTE_VIEW = 'ambiente.ver';

    public const AMBIENTE_CREATE = 'ambiente.crear';

    public const AMBIENTE_EDIT_STRUCTURE = 'ambiente.editar_estructura';

    public const AMBIENTE_EDIT_DATA = 'ambiente.editar_datos';

    public const AMBIENTE_CHANGE_STATUS = 'ambiente.cambiar_estado';

    /** Reservado para RF-016; la exportación aún no está implementada. */
    public const EXPORT = 'espacio.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CATALOG_VIEW,
            self::TIPO_SALA_ADMIN,
            self::TURNO_ADMIN,
            self::SALA_VIEW,
            self::SALA_CREATE,
            self::SALA_EDIT_STRUCTURE,
            self::SALA_EDIT_DATA,
            self::SALA_CHANGE_STATUS,
            self::AMBIENTE_VIEW,
            self::AMBIENTE_CREATE,
            self::AMBIENTE_EDIT_STRUCTURE,
            self::AMBIENTE_EDIT_DATA,
            self::AMBIENTE_CHANGE_STATUS,
            self::EXPORT,
        ];
    }
}
