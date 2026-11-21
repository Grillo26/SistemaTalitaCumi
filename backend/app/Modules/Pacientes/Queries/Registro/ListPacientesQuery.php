<?php

namespace App\Modules\Pacientes\Queries\Registro;

use App\Models\User;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Listado paginado de pacientes (04.01/RF-005).
 *
 * Selecciona solo columnas no sensibles: el listado nunca devuelve alergias,
 * condiciones ni observaciones.
 */
final class ListPacientesQuery
{
    public function __construct(private readonly PacienteAccessScope $scope) {}

    public function __invoke(
        ?string $search,
        ?string $estado,
        ?int $tipoDiagnosticoId,
        ?bool $sinDiagnostico,
        ?int $sucursalRegistroId,
        int $page,
        int $perPage,
        string $sort,
        string $direction,
        User $actor,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));
        $direction = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['nombre_completo', 'codigo', 'fecha_nacimiento', 'estado', 'updated_at'];
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'nombre_completo';

        $query = Paciente::query()
            ->select([
                'id',
                'codigo',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'fecha_nacimiento',
                'sexo',
                'ci',
                'tipo_diagnostico_id',
                'sucursal_registro_id',
                'estado',
                'version',
                'created_at',
                'updated_at',
            ])
            ->with('sucursalRegistro');

        $this->scope->apply($query, $actor, $sucursalRegistroId);

        $query
            ->buscar($search)
            ->estado($estado)
            ->tipoDiagnostico($tipoDiagnosticoId)
            ->sinDiagnostico($sinDiagnostico);

        if ($sort === 'nombre_completo') {
            $query
                ->orderBy('apellido_paterno_normalizado', $direction)
                ->orderBy('apellido_materno_normalizado', $direction)
                ->orderBy('nombre_normalizado', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}
