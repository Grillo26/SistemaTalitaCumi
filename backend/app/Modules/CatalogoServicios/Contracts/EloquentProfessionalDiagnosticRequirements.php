<?php

namespace App\Modules\CatalogoServicios\Contracts;

use App\Modules\CatalogoServicios\Queries\GetEspecialidadesRequeridasQuery;
use App\Modules\Profesionales\Contracts\ProfessionalDiagnosticRequirementsContract;

/**
 * Implementación real de los requisitos de especialidades para 03/07/11.
 */
final class EloquentProfessionalDiagnosticRequirements implements ProfessionalDiagnosticRequirementsContract
{
    public function __construct(private readonly GetEspecialidadesRequeridasQuery $query) {}

    public function especialidadesRequeridas(int $tipoDiagnosticoId): array
    {
        return array_map(
            static fn (array $item): array => [
                'especialidad_id' => (int) $item['especialidad_id'],
                'cantidad_minima' => (int) $item['cantidad_minima'],
            ],
            ($this->query)($tipoDiagnosticoId),
        );
    }
}
