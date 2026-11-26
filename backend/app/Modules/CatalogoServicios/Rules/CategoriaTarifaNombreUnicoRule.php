<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\CategoriaTarifaNombreDuplicadoException;

/**
 * Unicidad normalizada de categoría de tarifa (RF-T01).
 */
final class CategoriaTarifaNombreUnicoRule
{
    /**
     * @param  list<string>  $existingFoldedNames  sin incluir la categoría editada
     */
    public static function assertAvailable(
        string $candidateFoldedName,
        array $existingFoldedNames,
    ): void {
        if (in_array($candidateFoldedName, $existingFoldedNames, true)) {
            throw new CategoriaTarifaNombreDuplicadoException;
        }
    }
}
