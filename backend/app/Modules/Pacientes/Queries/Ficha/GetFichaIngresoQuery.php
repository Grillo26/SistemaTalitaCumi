<?php

namespace App\Modules\Pacientes\Queries\Ficha;

use App\Models\User;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Support\FichaIngresoAccessScope;

/**
 * Ficha vigente o una ficha concreta de un paciente (04.03/RF-009).
 */
final class GetFichaIngresoQuery
{
    public function __construct(private readonly FichaIngresoAccessScope $scope) {}

    public function __invoke(Paciente $paciente, User $actor, ?int $fichaId = null): ?FichaIngreso
    {
        $query = FichaIngreso::query()
            ->where('paciente_id', $paciente->id)
            ->with(['paciente', 'sucursalIngreso', 'tutorPrincipal']);

        $ficha = $fichaId === null
            ? $query->vigente()->latest('fecha_ingreso')->latest('id')->first()
            : $query->whereKey($fichaId)->first();

        if ($ficha === null) {
            if ($fichaId !== null) {
                throw new FichaIngresoNotFoundException($fichaId);
            }

            return null;
        }

        $this->scope->assertCanAccess($actor, $ficha);

        return $ficha;
    }
}
