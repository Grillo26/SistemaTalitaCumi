<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\AreaNombreDuplicadoException;

/**
 * Unicidad global de área (RN-C02 / RF-C03).
 *
 * Recibe las claves ya consultadas; la Action/Guard hace la lectura.
 */
final class AreaNombreUnicoRule
{
    /**
     * @param  list<string>  $existingFoldedNames  sin incluir el registro editado
     */
    public static function assertAvailable(string $candidateFoldedName, array $existingFoldedNames): void
    {
        if (in_array($candidateFoldedName, $existingFoldedNames, true)) {
            throw new AreaNombreDuplicadoException;
        }
    }
}
