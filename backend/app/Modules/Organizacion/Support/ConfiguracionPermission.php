<?php

namespace App\Modules\Organizacion\Support;

/**
 * Códigos de permiso del submódulo de configuración.
 *
 * La aplicación decide por permiso; los nombres de rol solo aparecen en el
 * seeder de la matriz.
 */
final class ConfiguracionPermission
{
    /** Ver todos los parámetros (Administración y Superadmin). */
    public const VIEW = 'parametro.ver';

    /** Ver solo parámetros operativos visibles para Recepción. */
    public const VIEW_OPERATIVES = 'parametro.ver_operativos';

    public const EDIT = 'parametro.editar';

    public const RESTORE = 'parametro.restaurar';

    /** Reservado para RF-015; la exportación aún no está implementada. */
    public const EXPORT = 'parametro.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::VIEW_OPERATIVES,
            self::EDIT,
            self::RESTORE,
            self::EXPORT,
        ];
    }
}
