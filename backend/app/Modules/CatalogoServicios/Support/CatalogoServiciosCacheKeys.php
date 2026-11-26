<?php

namespace App\Modules\CatalogoServicios\Support;

/**
 * Claves de caché del directorio expuesto a otros módulos (RF-C15).
 */
final class CatalogoServiciosCacheKeys
{
    public static function areas(): string
    {
        return 'catalogo_servicios:areas';
    }

    public static function servicios(): string
    {
        return 'catalogo_servicios:servicios';
    }

    public static function tiposServicio(): string
    {
        return 'catalogo_servicios:tipos_servicio';
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::areas(),
            self::servicios(),
            self::tiposServicio(),
        ];
    }
}
