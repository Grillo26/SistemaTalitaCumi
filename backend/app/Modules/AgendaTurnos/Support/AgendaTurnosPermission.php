<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Permisos del módulo 07 — Agenda y turnos.
 *
 * La aplicación decide por permiso, nunca por nombre de rol. Los permisos de
 * reprogramación/cancelación individual y asistencia pertenecen a 7.3 y 7.4
 * y no se crean en esta entrega.
 */
final class AgendaTurnosPermission
{
    public const VER = 'turno.ver';

    public const EXPORTAR = 'turno.exportar';

    public const GENERAR = 'turno.generar';

    public const REGENERAR = 'turno.regenerar';

    public const ASIGNAR_PROFESIONAL = 'turno.asignar_profesional';

    public const FERIADOS = 'turno.feriados';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VER,
            self::EXPORTAR,
            self::GENERAR,
            self::REGENERAR,
            self::ASIGNAR_PROFESIONAL,
            self::FERIADOS,
        ];
    }

    /**
     * Permisos del submódulo 7.1 (agenda semanal).
     *
     * Se separan de `all()` para que la matriz de 7.1 no marque ni asigne los
     * permisos de 7.2 en una instalación nueva.
     *
     * @return list<string>
     */
    public static function base(): array
    {
        return [
            self::VER,
            self::EXPORTAR,
        ];
    }

    /**
     * Permisos que administra el submódulo 7.2 (no reasigna los de 7.1).
     *
     * @return list<string>
     */
    public static function programacion(): array
    {
        return [
            self::GENERAR,
            self::REGENERAR,
            self::ASIGNAR_PROFESIONAL,
            self::FERIADOS,
        ];
    }

    private function __construct() {}
}
