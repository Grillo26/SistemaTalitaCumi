<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\ParameterConcurrencyException;
use App\Modules\Organizacion\Exceptions\ParameterNotEditableException;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;

/**
 * Lectura y edición encapsulada de un parámetro para módulos consumidores.
 *
 * La escritura reutiliza la Action de configuración de Organización, por lo
 * que conserva validación de tipo/rango, versión, caché y bitácora.
 */
interface ParameterAdminContract
{
    /**
     * @throws ParameterNotFoundException
     */
    public function detalle(string $clave): ParameterAdminValue;

    /**
     * @throws ParameterNotFoundException
     * @throws ParameterConcurrencyException
     * @throws ParameterNotEditableException
     */
    public function actualizar(
        string $clave,
        string $valor,
        int $version,
        ?int $actorId,
        ?string $motivo = null,
    ): ParameterAdminValue;
}
