<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionQueryBuilder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * [Estados Paciente e Inscripción] RF-011 / RF-014 / CA-011 / CA-012.
 */
final class ListInscripcionesPorEstadoQuery
{
    public function __construct(private readonly EstadoInscripcionQueryBuilder $builder) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(
        array $filters,
        User $actor,
        int $page = 1,
        int $perPage = 20,
        string $sort = 'fecha_fin',
        string $direction = 'asc',
    ): LengthAwarePaginator {
        return $this->builder->paginate(
            filters: $filters,
            actor: $actor,
            page: $page,
            perPage: $perPage,
            sort: $sort,
            direction: $direction,
        );
    }
}
