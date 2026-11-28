<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\BranchNotFoundException;
use App\Modules\Organizacion\Exceptions\InactiveBranchException;

/**
 * Directorio de sucursales para módulos consumidores (FP-04 / RN-003).
 *
 * Inscripciones, turnos, pagos y caja deben consultar este contrato antes de
 * operar para bloquear sucursales inactivas.
 */
interface BranchDirectoryContract
{
    public function existe(int $sucursalId): bool;

    public function estaActiva(int $sucursalId): bool;

    /**
     * Resúmenes en lote para selectores de módulos consumidores.
     *
     * @param  list<int>  $sucursalIds
     * @return array<int, BranchResumen>
     */
    public function resumenes(array $sucursalIds): array;

    /**
     * @throws InactiveBranchException
     * @throws BranchNotFoundException
     */
    public function asegurarActiva(int $sucursalId): void;
}
