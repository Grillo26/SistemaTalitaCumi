<?php

namespace App\Modules\Profesionales\Queries\Matriz;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;

/**
 * Opciones de filtros de la matriz (03/RF-E08).
 *
 * Profesionales y sucursales respetan el alcance; el catálogo de especialidades
 * es global.
 */
final class GetMatrizFilterOptionsQuery
{
    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    /**
     * @return array{
     *     profesionales: list<array{id: int, nombre: string, ci: string, activo: bool}>,
     *     especialidades: list<array{id: int, nombre: string, activo: bool}>,
     *     sucursales: list<array{id: int, codigo: string, nombre: string, activo: bool}>
     * }
     */
    public function __invoke(User $actor): array
    {
        $branchIds = $this->scope->accessibleBranchIds($actor);

        $profesionalesQuery = Profesional::query();
        $this->scope->apply($profesionalesQuery, $actor);

        $profesionales = $profesionalesQuery
            ->orderBy('nombre_completo')
            ->get(['id', 'nombre_completo', 'ci', 'activo'])
            ->map(static fn (Profesional $profesional): array => [
                'id' => $profesional->id,
                'nombre' => $profesional->nombre_completo,
                'ci' => $profesional->ci,
                'activo' => (bool) $profesional->activo,
            ])
            ->values()
            ->all();

        $especialidades = Especialidad::query()
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'activo'])
            ->map(static fn (Especialidad $especialidad): array => [
                'id' => $especialidad->id,
                'nombre' => $especialidad->nombre,
                'activo' => (bool) $especialidad->activo,
            ])
            ->values()
            ->all();

        $sucursalesQuery = Branch::query();

        if ($branchIds !== null) {
            if ($branchIds === []) {
                $sucursalesQuery->whereRaw('1 = 0');
            } else {
                $sucursalesQuery->whereIn('id', $branchIds);
            }
        }

        $sucursales = $sucursalesQuery
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'activo'])
            ->map(static fn (Branch $branch): array => [
                'id' => $branch->id,
                'codigo' => $branch->codigo,
                'nombre' => $branch->nombre,
                'activo' => (bool) $branch->activo,
            ])
            ->values()
            ->all();

        return [
            'profesionales' => $profesionales,
            'especialidades' => $especialidades,
            'sucursales' => $sucursales,
        ];
    }
}
