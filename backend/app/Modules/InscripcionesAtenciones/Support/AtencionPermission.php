<?php

namespace App\Modules\InscripcionesAtenciones\Support;

/**
 * Permisos del submódulo 03 (entrevistas y diagnósticos como atenciones).
 *
 * La aplicación decide por permiso, nunca por nombre de rol.
 */
final class AtencionPermission
{
    public const VIEW = 'atencion.ver';

    public const CREATE = 'atencion.crear';

    public const EDIT = 'atencion.editar';

    public const WITHDRAW = 'atencion.retirar';

    /** Reservado para exportación PDF/Excel; la infraestructura aún no existe. */
    public const EXPORT = 'atencion.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::WITHDRAW,
            self::EXPORT,
        ];
    }
}
