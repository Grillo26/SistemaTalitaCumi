<?php

namespace App\Modules\Seguridad\Queries\Usuarios;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Seguridad\Support\RoleLabel;
use Spatie\Permission\Models\Role;

/**
 * Catálogo de roles y sucursales activas para filtros y formularios
 * (02/RF-U04 / 02/RN-02, 02/RN-03).
 *
 * Las sucursales se leen en cada consulta: no se usa la foto del login.
 */
final class GetUserFormOptionsQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    /**
     * @return array{
     *     roles: list<array{id: int, nombre: string, label: string}>,
     *     sucursales: list<array{id: int, codigo: string, nombre: string}>
     * }
     */
    public function __invoke(User $actor): array
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(static fn (Role $role): array => [
                'id' => $role->id,
                'nombre' => $role->name,
                'label' => RoleLabel::for($role->name),
            ])
            ->values()
            ->all();

        $branchIds = $this->access->accesibles($actor->id);

        $branches = $branchIds === []
            ? collect()
            : Branch::query()
                ->whereIn('id', $branchIds)
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre'])
                ->map(static fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'codigo' => $branch->codigo,
                    'nombre' => $branch->nombre,
                ]);

        return [
            'roles' => $roles,
            'sucursales' => $branches->values()->all(),
        ];
    }
}
