<?php

namespace App\Modules\CatalogoServicios\Queries;

use App\Modules\CatalogoServicios\Exceptions\TipoDiagnosticoNotFoundException;
use App\Modules\CatalogoServicios\Models\TipoDiagnostico;

final class GetTipoDiagnosticoQuery
{
    public function __invoke(int $tipoDiagnosticoId): TipoDiagnostico
    {
        $tipo = TipoDiagnostico::query()
            ->with(['servicio.area', 'servicio.tipoServicio', 'especialidades'])
            ->withCount('especialidades')
            ->find($tipoDiagnosticoId);

        if ($tipo === null) {
            throw new TipoDiagnosticoNotFoundException($tipoDiagnosticoId);
        }

        return $tipo;
    }
}
