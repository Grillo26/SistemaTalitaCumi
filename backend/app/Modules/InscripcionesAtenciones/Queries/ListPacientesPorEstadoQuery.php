<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoContract;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * [Estados Paciente e Inscripción] RF-011 / CA-011.
 */
final class ListPacientesPorEstadoQuery
{
    public function __construct(private readonly PacienteEstadoContract $pacientes) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __invoke(
        array $filters,
        User $actor,
        int $page = 1,
        int $perPage = 20,
        string $sort = 'nombre_completo',
        string $direction = 'asc',
    ): LengthAwarePaginator {
        $filters['page'] = max(1, $page);
        $filters['per_page'] = max(1, min($perPage, 50));
        $filters['sort'] = $sort;
        $filters['direction'] = $direction === 'desc' ? 'desc' : 'asc';

        return $this->pacientes->listarPorEstado($filters, $actor->id);
    }
}
