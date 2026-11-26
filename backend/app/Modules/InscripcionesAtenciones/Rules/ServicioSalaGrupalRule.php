<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\ServicioNoEsSalaGrupalException;

/**
 * El servicio de una inscripción a sala grupal debe ser del tipo `sala_grupal`.
 */
final class ServicioSalaGrupalRule
{
    public const CODIGO_SALA_GRUPAL = 'sala_grupal';

    public static function assertEsSalaGrupal(?string $codigo, string $servicioNombre): void
    {
        if ($codigo !== self::CODIGO_SALA_GRUPAL) {
            throw new ServicioNoEsSalaGrupalException($servicioNombre);
        }
    }
}
