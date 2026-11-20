<?php

namespace App\Modules\Organizacion\Support;

/**
 * Claves y TTL de la caché de parámetros (RNF-002 / RT-008).
 */
final class ParameterCacheKeys
{
    public const PARAMETER_PREFIX = 'configuracion.parametro.';

    public const CATEGORY_PREFIX = 'configuracion.categoria.';

    public const TTL_SECONDS = 3600;

    public static function forParameter(string $clave): string
    {
        return self::PARAMETER_PREFIX.$clave;
    }

    public static function forCategory(string $categoria): string
    {
        return self::CATEGORY_PREFIX.$categoria;
    }
}
