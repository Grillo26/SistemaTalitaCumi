<?php

namespace App\Modules\Profesionales\Queries\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Exceptions\ProfesionalNotFoundException;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;

/**
 * Ficha de profesional con especialidades y sucursales (03/RF-D05).
 */
final class GetProfesionalQuery
{
    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    public function __invoke(int $profesionalId, User $actor): Profesional
    {
        $profesional = Profesional::query()
            ->with([
                'relacionesEspecialidad.especialidad',
                'relacionesSucursal.sucursal',
            ])
            ->find($profesionalId);

        if ($profesional === null) {
            throw new ProfesionalNotFoundException($profesionalId);
        }

        $this->scope->assertCanAccess($actor, $profesional);

        return $profesional;
    }
}
