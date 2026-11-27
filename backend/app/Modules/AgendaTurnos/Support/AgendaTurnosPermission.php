<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Permisos del submódulo 01 — agenda semanal por sucursal.
 *
 * La aplicación decide por permiso, nunca por nombre de rol. Los permisos de
 * asistencia y reprogramación/cancelación pertenecen a 7.3 y 7.4 y no se
 * crean en esta entrega.
 */
final class AgendaTurnosPermission
{
    public const VER = 'turno.ver';

    public const EXPORTAR = 'turno.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VER,
            self::EXPORTAR,
        ];
    }

    private function __construct() {}
}
