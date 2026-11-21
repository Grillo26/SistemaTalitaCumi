<?php

namespace App\Modules\Profesionales\Actions\Directorio;

use App\Modules\Profesionales\Exceptions\DeletionNotAllowedException;
use App\Modules\Profesionales\Exceptions\ProfesionalSucursalNotFoundException;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Models\ProfesionalSucursal;

/**
 * Rechazo explícito de eliminación de la relación profesional–sucursal
 * (03/RF-E11 / 03/RN-04).
 */
final class RejectProfesionalSucursalDeletionAction
{
    public function __invoke(Profesional $profesional, int $sucursalId): never
    {
        $relation = ProfesionalSucursal::query()
            ->where('profesional_id', $profesional->id)
            ->where('sucursal_id', $sucursalId)
            ->first();

        if ($relation === null) {
            throw new ProfesionalSucursalNotFoundException($profesional->id, $sucursalId);
        }

        throw new DeletionNotAllowedException(
            'la relación con la sucursal',
            (string) $sucursalId,
            'PROFESIONAL_SUCURSAL_DELETE_NOT_ALLOWED',
        );
    }
}
