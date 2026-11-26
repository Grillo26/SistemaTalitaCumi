<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\ServicioNoEsDiagnosticoException;

/**
 * RN-D08: el servicio asociado debe ser del tipo diagnóstico/evaluación.
 */
final class TipoDiagnosticoServicioRule
{
    public const CODIGO_DIAGNOSTICO = 'diagnostico_evaluacion';

    public static function assertEsDiagnostico(?string $codigo, string $servicioNombre): void
    {
        if ($codigo !== self::CODIGO_DIAGNOSTICO) {
            throw new ServicioNoEsDiagnosticoException($servicioNombre);
        }
    }
}
