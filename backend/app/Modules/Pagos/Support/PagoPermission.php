<?php

namespace App\Modules\Pagos\Support;

/**
 * Permisos del submódulo 08.01 (RN-003 del 8.4 queda para anulación; no se crea aquí).
 */
final class PagoPermission
{
    public const VIEW = 'pago.ver';

    public const REGISTER = 'pago.registrar';

    public const AUTHORIZE_FUTURE = 'pago.autorizar_fecha_futura';

    public const EXPORT = 'pago.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::REGISTER,
            self::AUTHORIZE_FUTURE,
            self::EXPORT,
        ];
    }
}
