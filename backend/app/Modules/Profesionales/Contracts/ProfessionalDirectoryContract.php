<?php

namespace App\Modules\Profesionales\Contracts;

use App\Modules\Profesionales\Exceptions\ProfesionalInactivoException;
use App\Modules\Profesionales\Exceptions\ProfesionalNotFoundException;

/**
 * Directorio mínimo de profesionales para módulos consumidores
 * (03/FP-07, 03/RF-D12, 03/RF-D13, 03/RF-A11).
 */
interface ProfessionalDirectoryContract
{
    public function existe(int $profesionalId): bool;

    public function estaActivo(int $profesionalId): bool;

    /**
     * Nombres completos en lote, incluyendo profesionales inactivos para no
     * perder turnos históricos.
     *
     * @param  list<int>  $profesionalIds
     * @return array<int, string>
     */
    public function nombres(array $profesionalIds): array;

    /**
     * Profesionales activos con relación activa a la sucursal.
     *
     * @return array<int, string>
     */
    public function nombresPorSucursal(int $sucursalId, bool $soloActivos = true): array;

    /**
     * @throws ProfesionalNotFoundException
     * @throws ProfesionalInactivoException
     */
    public function asegurarActivo(int $profesionalId): void;
}
