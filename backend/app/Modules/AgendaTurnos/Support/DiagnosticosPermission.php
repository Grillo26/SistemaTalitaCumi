<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Permisos del submódulo 05 — diagnóstico con varios profesionales.
 *
 * Reutiliza los permisos reservados por el módulo 03 y crea solo los códigos
 * propios. La aplicación decide por permiso, nunca por nombre de rol.
 */
final class DiagnosticosPermission
{
    /** Listado, coordinación y consulta del detalle. */
    public const VER = 'asignacion.ver';

    /** Programar el turno de un profesional. */
    public const PROGRAMAR = 'asignacion.crear';

    /** Exportar el reporte CSV. */
    public const EXPORTAR = 'asignacion.exportar';

    /** Cancelar el diagnóstico completo. */
    public const CANCELAR = 'diagnostico_agenda.cancelar';

    /** Actualizar manualmente el estado general. */
    public const ACTUALIZAR_ESTADO = 'diagnostico_agenda.actualizar_estado';

    /** Ver el teléfono y el enlace de WhatsApp del tutor. */
    public const WHATSAPP = 'tutor.ver_contacto';

    /**
     * Permisos nuevos que crea este submódulo.
     *
     * @return list<string>
     */
    public static function nuevos(): array
    {
        return [
            self::CANCELAR,
            self::ACTUALIZAR_ESTADO,
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return self::nuevos();
    }

    private function __construct() {}
}
