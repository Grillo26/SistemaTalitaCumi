<?php

namespace App\Modules\Pacientes\Contracts;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoContract;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoResumen;
use App\Modules\Pacientes\Actions\Registro\ChangePacienteStatusAction;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Queries\Registro\ListPacientesQuery;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Implementación real del contrato de estado de paciente para 06.05.
 *
 * Reutiliza la Action, las Queries y el historial existentes en Pacientes.
 */
final class EloquentPacienteEstado implements PacienteEstadoContract
{
    public function __construct(
        private readonly GetPacienteQuery $getPaciente,
        private readonly ListPacientesQuery $listPacientes,
        private readonly ChangePacienteStatusAction $changeStatus,
    ) {}

    public function resumen(int $pacienteId, int $actorId): PacienteEstadoResumen
    {
        $actor = $this->actor($actorId);

        return $this->toResumen(($this->getPaciente)($pacienteId, $actor));
    }

    public function cambiarEstado(
        int $pacienteId,
        EstadoPaciente $nuevoEstado,
        string $motivo,
        int $versionEsperada,
        int $actorId,
        ?string $ip = null,
    ): PacienteEstadoResumen {
        $actor = $this->actor($actorId);
        $paciente = ($this->getPaciente)($pacienteId, $actor);
        $actualizado = ($this->changeStatus)(
            $paciente,
            $nuevoEstado,
            $motivo,
            $versionEsperada,
            $actor,
            $ip,
        );

        return $this->toResumen($actualizado);
    }

    public function listarPorEstado(array $filtros, int $actorId): LengthAwarePaginator
    {
        $actor = $this->actor($actorId);
        $page = max(1, (int) ($filtros['page'] ?? 1));
        $perPage = max(1, min(50, (int) ($filtros['per_page'] ?? 20)));

        $paginator = ($this->listPacientes)(
            search: isset($filtros['search']) ? (string) $filtros['search'] : null,
            estado: isset($filtros['estado']) ? (string) $filtros['estado'] : null,
            tipoDiagnosticoId: null,
            sinDiagnostico: null,
            sucursalRegistroId: isset($filtros['sucursal_id']) && $filtros['sucursal_id'] !== ''
                ? (int) $filtros['sucursal_id']
                : null,
            page: $page,
            perPage: $perPage,
            sort: isset($filtros['sort']) ? (string) $filtros['sort'] : 'nombre_completo',
            direction: ($filtros['direction'] ?? 'asc') === 'desc' ? 'desc' : 'asc',
            actor: $actor,
            puedeBuscarTelefonoTutor: $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT),
        );

        $paginator->getCollection()->load('tutores');

        return $paginator->through(fn (Paciente $paciente): PacienteEstadoResumen => $this->toResumen($paciente));
    }

    private function actor(int $actorId): User
    {
        return User::query()->findOrFail($actorId);
    }

    private function toResumen(Paciente $paciente): PacienteEstadoResumen
    {
        $sucursal = $paciente->relationLoaded('sucursalRegistro')
            ? $paciente->sucursalRegistro
            : null;
        $tutor = $paciente->relationLoaded('tutores')
            ? $paciente->tutores->first(
                static fn ($tutor): bool => (bool) $tutor->pivot->es_principal,
            )
            : null;

        return new PacienteEstadoResumen(
            id: (int) $paciente->id,
            codigo: $paciente->codigo,
            nombreCompleto: $paciente->nombre_completo,
            estado: $paciente->estado,
            version: (int) $paciente->version,
            sucursalRegistroId: (int) $paciente->sucursal_registro_id,
            sucursalNombre: $sucursal?->nombre,
            tutorPrincipal: $tutor?->nombre_completo,
        );
    }
}
