<?php

namespace App\Modules\InscripcionesAtenciones\Support;

use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;

/**
 * Permisos del submódulo 04.
 *
 * No hay un permiso `vigencia.renovar`: la renovación exige el permiso de la
 * entidad subyacente.
 */
final class VigenciaPermission
{
    public const VIEW = 'vigencia.ver';

    public const REPORTS = 'vigencia.reportes';

    public const EXPORT = 'vigencia.exportar';

    public const CONFIG = 'vigencia.configurar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [self::VIEW, self::REPORTS, self::EXPORT, self::CONFIG];
    }

    public static function viewFor(TipoEntidadVigencia $tipo): string
    {
        return $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? InscripcionPermission::VIEW
            : PaquetePermission::VIEW;
    }

    public static function renewFor(TipoEntidadVigencia $tipo): string
    {
        return $tipo === TipoEntidadVigencia::INSCRIPCION_SALA
            ? InscripcionPermission::RENEW
            : PaquetePermission::RENEW;
    }
}
