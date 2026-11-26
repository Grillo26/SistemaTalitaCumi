<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\ServicioNombreDuplicadoException;

/**
 * Unicidad de servicio dentro de su área (RN-C03 / RF-C06).
 *
 * El mismo nombre puede existir en otra área.
 */
final class ServicioNombreUnicoRule
{
    /**
     * @param  list<string>  $existingFoldedNames  del área destino, sin el registro editado
     */
    public static function assertAvailable(string $candidateFoldedName, array $existingFoldedNames): void
    {
        if (in_array($candidateFoldedName, $existingFoldedNames, true)) {
            throw new ServicioNombreDuplicadoException;
        }
    }
}
