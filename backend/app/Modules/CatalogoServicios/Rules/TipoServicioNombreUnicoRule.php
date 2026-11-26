<?php

namespace App\Modules\CatalogoServicios\Rules;

use App\Modules\CatalogoServicios\Exceptions\TipoServicioNombreDuplicadoException;

/**
 * Unicidad global de tipo de servicio (RN-C09).
 */
final class TipoServicioNombreUnicoRule
{
    /**
     * @param  list<string>  $existingFoldedNames
     */
    public static function assertAvailable(string $candidateFoldedName, array $existingFoldedNames): void
    {
        if (in_array($candidateFoldedName, $existingFoldedNames, true)) {
            throw new TipoServicioNombreDuplicadoException;
        }
    }
}
