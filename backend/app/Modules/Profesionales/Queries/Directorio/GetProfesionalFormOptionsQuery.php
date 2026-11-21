<?php

namespace App\Modules\Profesionales\Queries\Directorio;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Support\ProfesionalesModule;

/**
 * Opciones para formularios y filtros del directorio.
 *
 * Las sucursales se leen en cada consulta según el alcance real del usuario;
 * no se usa la foto del login.
 */
final class GetProfesionalFormOptionsQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return array{
     *     especialidades: list<array{id: int, nombre: string}>,
     *     sucursales: list<array{id: int, codigo: string, nombre: string}>,
     *     puede_secundarias: bool
     * }
     */
    public function __invoke(User $actor): array
    {
        $especialidades = Especialidad::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre'])
            ->map(static fn (Especialidad $especialidad): array => [
                'id' => $especialidad->id,
                'nombre' => $especialidad->nombre,
            ])
            ->values()
            ->all();

        $branchIds = $this->access->accesibles($actor->id);

        $sucursales = $branchIds === []
            ? []
            : Branch::query()
                ->whereIn('id', $branchIds)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre'])
                ->map(static fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'codigo' => $branch->codigo,
                    'nombre' => $branch->nombre,
                ])
                ->values()
                ->all();

        return [
            'especialidades' => $especialidades,
            'sucursales' => $sucursales,
            'puede_secundarias' => ProfesionalesModule::SECUNDARIAS_HABILITADAS,
        ];
    }
}
