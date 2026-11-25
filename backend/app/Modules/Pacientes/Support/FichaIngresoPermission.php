<?php

namespace App\Modules\Pacientes\Support;

/**
 * Permisos de la ficha de ingreso (04.03 Fase A).
 */
final class FichaIngresoPermission
{
    public const VIEW = 'ficha_ingreso.ver';

    public const CREATE = 'ficha_ingreso.crear';

    public const EDIT = 'ficha_ingreso.editar';

    public const ARCHIVE = 'ficha_ingreso.archivar';

    public const REACTIVATE = 'ficha_ingreso.reactivar';

    public const VIEW_SENSITIVE = 'ficha_ingreso.ver_sensibles';

    /** Reservado para infraestructura PDF (04.03/RF-010). */
    public const EXPORT = 'ficha_ingreso.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::CREATE,
            self::EDIT,
            self::ARCHIVE,
            self::REACTIVATE,
            self::VIEW_SENSITIVE,
            self::EXPORT,
        ];
    }
}
