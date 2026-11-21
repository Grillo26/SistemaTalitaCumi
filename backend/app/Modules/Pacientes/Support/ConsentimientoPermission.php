<?php

namespace App\Modules\Pacientes\Support;

/**
 * Permisos de consentimientos de la ficha de ingreso (04.03 Fase B).
 */
final class ConsentimientoPermission
{
    public const VIEW = 'ficha_ingreso.consentimiento_ver';

    public const REGISTER = 'ficha_ingreso.consentimiento_registrar';

    public const REVOKE = 'ficha_ingreso.consentimiento_revocar';

    public const CATALOG = 'ficha_ingreso.consentimiento_catalogo';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::REGISTER,
            self::REVOKE,
            self::CATALOG,
        ];
    }
}
