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

    public static function tarifas(): string
    {
        return 'catalogo_servicios:tarifas';
    }

    public static function tiposDiagnostico(): string
    {
        return 'catalogo_servicios:tipos_diagnostico';
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
            self::tarifas(),
            self::tiposDiagnostico(),
        ];
    }
}
