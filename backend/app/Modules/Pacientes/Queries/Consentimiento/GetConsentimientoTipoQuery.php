<?php

namespace App\Modules\Pacientes\Queries\Consentimiento;

use App\Modules\Pacientes\Exceptions\ConsentimientoTipoNotFoundException;
use App\Modules\Pacientes\Models\ConsentimientoTipo;

/**
 * Tipo de consentimiento del catálogo (04.03 Fase B).
 */
final class GetConsentimientoTipoQuery
{
    public function __invoke(int $tipoId): ConsentimientoTipo
    {
        $tipo = ConsentimientoTipo::query()->find($tipoId);

        if ($tipo === null) {
            throw new ConsentimientoTipoNotFoundException($tipoId);
        }

        return $tipo;
    }
}
