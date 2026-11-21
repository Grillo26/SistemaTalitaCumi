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
     * @throws ProfesionalNotFoundException
     * @throws ProfesionalInactivoException
     */
    public function asegurarActivo(int $profesionalId): void;
}
