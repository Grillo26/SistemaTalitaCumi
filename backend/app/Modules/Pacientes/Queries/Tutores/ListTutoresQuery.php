<?php

namespace App\Modules\Pacientes\Queries\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Support\TutorAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado/buscador de tutores (04.02/RF-007).
 */
final class ListTutoresQuery
{
    public function __construct(private readonly TutorAccessScope $scope) {}

    public function __invoke(
        ?string $search,
        ?bool $activo,
        int $page,
        int $perPage,
        string $sort,
        string $direction,
        User $actor,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['nombre_completo', 'ci', 'activo', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'nombre_completo';

        $query = Tutor::query();
        $this->scope->apply($query, $actor);

        $query->buscar($search)->activo($activo);

        if ($sort === 'nombre_completo') {
            $query->orderBy('nombre_completo_normalizado', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
