<?php

namespace App\Modules\Pacientes\Queries\Registro;

use App\Models\User;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Rules\PacienteBusquedaRule;
use App\Modules\Pacientes\Support\PacienteAccessScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

/**
 * Listado y búsqueda paginada de pacientes (04.01/RF-005, 04.04 Fase A).
 *
 * Selecciona solo columnas no sensibles: el listado nunca devuelve alergias,
 * condiciones ni observaciones. La búsqueda por teléfono del tutor solo se
 * activa si el actor tiene `tutor.ver_contacto`.
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
        bool $puedeBuscarTelefonoTutor = false,
    ): LengthAwarePaginator {
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        return $this->builder(
            search: $search,
            estado: $estado,
            tipoDiagnosticoId: $tipoDiagnosticoId,
            sinDiagnostico: $sinDiagnostico,
            sucursalRegistroId: $sucursalRegistroId,
            sort: $sort,
            direction: $direction,
            actor: $actor,
            puedeBuscarTelefonoTutor: $puedeBuscarTelefonoTutor,
        )
            ->paginate($perPage, ['*'], 'page', $page)
            ->withQueryString();
    }

    /**
     * Query reutilizable por el listado paginado y la exportación CSV.
     */
    public function builder(
        ?string $search,
        ?string $estado,
        ?int $tipoDiagnosticoId,
        ?bool $sinDiagnostico,
        ?int $sucursalRegistroId,
        string $sort,
        string $direction,
        User $actor,
        bool $puedeBuscarTelefonoTutor = false,
    ): Builder {
        PacienteBusquedaRule::assertValid($search);

        $direction = $direction === 'desc' ? 'desc' : 'asc';
        $sort = PacienteBusquedaRule::isAllowedSort($sort) ? $sort : 'nombre_completo';
        $telefonoTutor = $puedeBuscarTelefonoTutor
            ? PacienteBusquedaRule::tutorPhone($search)
            : null;

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
            ->buscar($search, $telefonoTutor)
            ->estado($estado)
            ->tipoDiagnostico($tipoDiagnosticoId)
            ->sinDiagnostico($sinDiagnostico);

        if ($sort === 'nombre_completo' || $sort === 'apellido') {
            $query
                ->orderBy('apellido_paterno_normalizado', $direction)
                ->orderBy('apellido_materno_normalizado', $direction)
                ->orderBy('nombre_normalizado', $direction);
        } else {
            $query->orderBy($sort, $direction);
        }

        return $query;
    }
}
