<?php

namespace App\Modules\Profesionales\Queries\Directorio;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Support\ProfesionalesPermission;

/**
 * Sucursales visibles para un profesional, con el estado de la relación
 * (03/RF-E06 / 03/RN-18).
 *
 * Nunca devuelve sucursales fuera del alcance del usuario. El alcance global
 * ve todas las sucursales, incluidas las inactivas, para poder gestionar la
 * relación sin ocultar datos.
 */
final class ListProfesionalSucursalesQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(Profesional $profesional, User $actor): array
    {
        $isGlobal = $actor->can(ProfesionalesPermission::GLOBAL_SCOPE);
        $branchIds = $isGlobal ? null : $this->access->accesibles($actor->id);

        if (! $isGlobal && $branchIds === []) {
            return [];
        }

        $relations = $profesional->relacionesSucursal()
            ->get()
            ->keyBy('sucursal_id');

        $query = Branch::query();

        if ($branchIds !== null) {
            $query->whereIn('id', $branchIds);
        }

        return $query
            ->orderBy('nombre')
            ->get(['id', 'codigo', 'nombre', 'activo'])
            ->map(static function (Branch $branch) use ($relations): array {
                $relation = $relations->get($branch->id);

                return [
                    'id' => $branch->id,
                    'codigo' => $branch->codigo,
                    'nombre' => $branch->nombre,
                    'activo' => (bool) $branch->activo,
                    'relacion_id' => $relation?->id,
                    'activo_relacion' => $relation?->activo,
                    'motivo' => $relation?->motivo,
                    'version' => $relation?->version,
                ];
            })
            ->values()
            ->all();
    }
}
