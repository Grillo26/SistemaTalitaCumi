<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

/**
 * [Estados Paciente e Inscripción] RF-013.
 *
 * No existe un permiso genérico de "cambiar estado": cada acción tiene el
 * suyo. El retiro de una inscripción reutiliza el permiso del submódulo de
 * origen.
 */
final class EstadoPermission
{
    public const VIEW = 'estado.ver';

    public const REPORTS = 'estado.reportes';

    public const EXPORT = 'estado.exportar';

    public const INSCRIPTION_PAUSE = 'inscripcion.pausar';

    public const INSCRIPTION_REACTIVATE = 'inscripcion.reactivar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::REPORTS,
            self::EXPORT,
            self::INSCRIPTION_PAUSE,
            self::INSCRIPTION_REACTIVATE,
        ];
    }

    public static function viewFor(TipoEntidadVigencia $tipo): string
    {
        return $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? InscripcionPermission::VIEW
            : PaquetePermission::VIEW;
    }

    public static function withdrawFor(TipoEntidadVigencia $tipo): string
    {
        return $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? InscripcionPermission::WITHDRAW
            : PaquetePermission::WITHDRAW;
    }
}
