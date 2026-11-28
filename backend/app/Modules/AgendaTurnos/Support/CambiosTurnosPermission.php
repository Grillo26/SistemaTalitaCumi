<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Permisos del submódulo 03 — reprogramación y cancelación.
 *
 * La decisión es siempre por permiso, nunca por nombre de rol.
 */
final class CambiosTurnosPermission
{
    public const REPROGRAMAR = 'turno.reprogramar';

    public const CANCELAR = 'turno.cancelar';

    public const CANCELAR_MASIVO = 'turno.cancelar_masivo';

    public const HISTORIAL = 'turno.historial';

    public const REPORTE = 'turno.reporte_cambios';

    public const EXPORTAR = 'turno.exportar_cambios';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::REPROGRAMAR,
            self::CANCELAR,
            self::CANCELAR_MASIVO,
            self::HISTORIAL,
            self::REPORTE,
            self::EXPORTAR,
        ];
    }

    /**
     * Permisos que asigna la matriz de 7.3 en la primera inicialización.
     *
     * @return list<string>
     */
    public static function changes(): array
    {
        return self::all();
    }

    private function __construct() {}
}
