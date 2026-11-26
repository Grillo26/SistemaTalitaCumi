<?php

namespace App\Modules\InscripcionesAtenciones\Support;

/**
 * Permisos del submódulo 01 (inscripción a salas grupales).
 *
 * La aplicación decide por permiso, nunca por nombre de rol.
 */
final class InscripcionPermission
{
    public const VIEW = 'inscripcion.ver';

    public const CREATE = 'inscripcion.crear';

    public const EDIT = 'inscripcion.editar';

    public const RENEW = 'inscripcion.renovar';

    public const WITHDRAW = 'inscripcion.retirar';

    /** Reservado para RF-015; la exportación aún no está disponible. */
    public const EXPORT = 'inscripcion.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::RENEW,
            self::WITHDRAW,
            self::EXPORT,
        ];
    }
}
