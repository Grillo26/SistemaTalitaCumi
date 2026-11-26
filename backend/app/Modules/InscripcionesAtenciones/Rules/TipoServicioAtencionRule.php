<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\ServicioNoEsEntrevistaException;

/**
 * El servicio de una entrevista debe ser del tipo `entrevista` (RF-001).
 */
final class TipoServicioAtencionRule
{
    public const CODIGO_ENTREVISTA = 'entrevista';

    public static function assertEsEntrevista(?string $codigo, string $servicioNombre): void
    {
        if ($codigo !== self::CODIGO_ENTREVISTA) {
            throw new ServicioNoEsEntrevistaException($servicioNombre);
        }
    }
}
