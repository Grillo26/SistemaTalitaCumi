<?php

namespace App\Modules\Pacientes\Support;

/**
 * Permisos de observaciones fechadas (04.03 Fase C).
 */
final class ObservacionPermission
{
    public const VIEW = 'ficha_ingreso.observacion_ver';

    public const ADD = 'ficha_ingreso.observacion_agregar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::VIEW,
            self::ADD,
        ];
    }
}
