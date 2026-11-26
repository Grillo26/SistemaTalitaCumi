<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNombreDuplicadoException;

/**
 * Unicidad normalizada de tipo de diagnóstico (RN-D02).
 */
final class TipoDiagnosticoNombreUnicoRule
{
    /**
     * @param  list<string>  $existingFoldedNames  sin incluir el tipo editado
     */
    public static function assertAvailable(
        string $candidateFoldedName,
        array $existingFoldedNames,
    ): void {
        if (in_array($candidateFoldedName, $existingFoldedNames, true)) {
            throw new TipoDiagnosticoNombreDuplicadoException;
        }
    }
}
