<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\EspacioInactivoException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;

/**
 * Directorio de ambientes para módulos consumidores (RF-013/RF-014).
 *
 * TODO módulo 07: agregar `ambiente_id` a la tabla `turno` y consumir este
 * contrato para las terapias individuales, entrevistas y diagnósticos.
 */
interface AmbienteDirectoryContract
{
    public function existe(int $ambienteId): bool;

    public function estaActiva(int $ambienteId): bool;

    public function perteneceASucursal(int $ambienteId, int $sucursalId): bool;

    /**
     * @throws EspacioNotFoundException
     * @throws EspacioInactivoException
     * @throws EspacioSucursalIncorrectaException
     */
    public function asegurarActivaEnSucursal(int $ambienteId, int $sucursalId): void;
}
