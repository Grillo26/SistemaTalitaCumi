<?php

namespace App\Modules\Profesionales\Contracts;

/**
 * Directorio de especialidades para módulos consumidores.
 *
 * No reemplaza la matriz profesional–especialidad–sucursal: solo expone
 * existencia, estado y cantidad de profesionales activos para una sucursal.
 */
interface EspecialidadDirectoryContract
{
    public function existe(int $especialidadId): bool;

    public function estaActiva(int $especialidadId): bool;

    /**
     * Profesionales activos que cubren la especialidad y trabajan en la sucursal.
     */
    public function profesionalesActivosPorEspecialidad(
        int $especialidadId,
        int $sucursalId,
    ): int;
}
