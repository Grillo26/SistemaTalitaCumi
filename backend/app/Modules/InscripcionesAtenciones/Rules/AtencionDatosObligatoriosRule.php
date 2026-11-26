<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\AtencionDatosIncompletosException;

/**
 * RN-007: datos obligatorios de una atención.
 */
final class AtencionDatosObligatoriosRule
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public static function assertCompletos(array $datos): void
    {
        $faltantes = [];

        foreach ($datos as $campo => $valor) {
            if ($valor === null || (is_string($valor) && trim($valor) === '')) {
                $faltantes[] = $campo;
            }
        }

        if ($faltantes !== []) {
            throw new AtencionDatosIncompletosException($faltantes);
        }
    }
}
