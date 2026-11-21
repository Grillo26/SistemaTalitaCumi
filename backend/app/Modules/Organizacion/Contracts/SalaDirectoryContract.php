<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\EspacioInactivoException;
use App\Modules\Organizacion\Exceptions\EspacioNotFoundException;
use App\Modules\Organizacion\Exceptions\EspacioSucursalIncorrectaException;

/**
 * Directorio de salas para módulos consumidores (RF-013/RF-014).
 *
 * Lo usarán Inscripciones a salas grupales (módulo 06) y Agenda y turnos
 * (módulo 07) antes de referenciar una sala.
 */
interface SalaDirectoryContract
{
    public function existe(int $salaId): bool;

    public function estaActiva(int $salaId): bool;

    public function perteneceASucursal(int $salaId, int $sucursalId): bool;

    /**
     * @throws EspacioNotFoundException
     * @throws EspacioInactivoException
     * @throws EspacioSucursalIncorrectaException
     */
    public function asegurarActivaEnSucursal(int $salaId, int $sucursalId): void;
}
