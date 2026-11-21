<?php

namespace App\Modules\Profesionales\Contracts;

use App\Modules\Profesionales\Exceptions\ProfesionalNotAssignableException;

/**
 * Validador reutilizable de la matriz profesional–especialidad–sucursal
 * (03/FP-07, 03/RF-E09, 03/RF-A03, 03/RF-A11).
 *
 * No verifica solapamiento ni el estado del turno: eso pertenece al módulo 07.
 */
interface ProfessionalAssignmentValidatorContract
{
    public function puedeAsignar(
        int $profesionalId,
        int $especialidadId,
        int $sucursalId,
    ): ResultadoAsignacion;

    /**
     * @throws ProfesionalNotAssignableException
     */
    public function asegurarAsignable(
        int $profesionalId,
        int $especialidadId,
        int $sucursalId,
    ): void;
}
