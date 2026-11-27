<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Permisos del submódulo 04 — asistencia por turno.
 *
 * La aplicación decide por permiso, nunca por nombre de rol.
 */
final class AsistenciaTurnosPermission
{
    public const VER = 'asistencia.ver';

    public const REGISTRAR = 'asistencia.registrar';

    public const CORREGIR_FUERA_VENTANA = 'asistencia.corregir_fuera_ventana';

    public const VER_INDICADORES = 'asistencia.ver_indicadores';

    public const RESUMEN_DIARIO = 'asistencia.resumen_diario';

    public const EXPORTAR = 'asistencia.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VER,
            self::REGISTRAR,
            self::CORREGIR_FUERA_VENTANA,
            self::VER_INDICADORES,
            self::RESUMEN_DIARIO,
            self::EXPORTAR,
        ];
    }

    /**
     * Permisos que asigna la matriz inicial de 07.04.
     *
     * @return list<string>
     */
    public static function changes(): array
    {
        return self::all();
    }

    private function __construct() {}
}
