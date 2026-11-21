<?php

namespace App\Modules\Seguridad\Queries\Usuarios;

use App\Models\User;
use App\Modules\Seguridad\Support\UserAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado paginado de usuarios con filtros y scoping (02/RF-U06, 02/RF-R07).
 */
final class ListUsersQuery
{
    public function __construct(private readonly UserAccessScope $scope) {}

    public function __invoke(
        ?string $search,
        ?int $roleId,
        ?int $branchId,
        ?string $estado,
        int $page,
        int $perPage,
        string $sort,
        string $direction,
        User $actor,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $allowedSorts = [
            'nombre' => 'name',
            'name' => 'name',
            'email' => 'email',
            'activo' => 'activo',
            'ultimo_acceso' => 'ultimo_acceso',
            'created_at' => 'created_at',
        ];

        $sortColumn = $allowedSorts[$sort] ?? 'name';
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $query = User::query()
            ->with([
                'roles:id,name',
                'branches:id,codigo,nombre',
            ])
            ->withCount([
                'tokens as sesiones_activas' => static fn (Builder $tokens) => $tokens->where(
                    static fn (Builder $tokens) => $tokens
                        ->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now()),
                ),
            ]);

        $this->scope->apply($query, $actor);

        if ($roleId !== null) {
            $query->whereHas(
                'roles',
                static fn (Builder $roles) => $roles->where('roles.id', $roleId),
            );
        }

        if ($branchId !== null) {
            $query->whereHas(
                'branches',
                static fn (Builder $branches) => $branches->where('sucursal.id', $branchId),
            );
        }

        return $query
            ->buscar($search)
            ->estado($estado)
            ->orderBy($sortColumn, $direction)
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
