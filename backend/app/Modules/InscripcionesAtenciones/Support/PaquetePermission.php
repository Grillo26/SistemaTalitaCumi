<?php

namespace App\Modules\InscripcionesAtenciones\Support;

/**
 * Permisos del submódulo 02 (paquetes de sesiones individuales).
 */
final class PaquetePermission
{
    public const VIEW = 'paquete.ver';

    public const CREATE = 'paquete.crear';

    public const EDIT = 'paquete.editar';

    public const RENEW = 'paquete.renovar';

    public const WITHDRAW = 'paquete.retirar';

    /** Reservado para exportación; el exporter sigue en stub. */
    public const EXPORT = 'paquete.exportar';

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
