<?php

namespace App\Modules\Pacientes\Http\Controllers\Ficha;

use App\Models\User;
use App\Modules\Pacientes\Actions\Ficha\ArchiveFichaIngresoAction;
use App\Modules\Pacientes\Actions\Ficha\CompleteFichaIngresoAction;
use App\Modules\Pacientes\Actions\Ficha\CreateFichaIngresoAction;
use App\Modules\Pacientes\Actions\Ficha\ReactivateFichaIngresoAction;
use App\Modules\Pacientes\Actions\Ficha\RejectFichaIngresoDeletionAction;
use App\Modules\Pacientes\Actions\Ficha\UpdateFichaIngresoAction;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Http\Requests\Ficha\ArchiveFichaIngresoRequest;
use App\Modules\Pacientes\Http\Requests\Ficha\FichaVersionRequest;
use App\Modules\Pacientes\Http\Requests\Ficha\ReactivateFichaIngresoRequest;
use App\Modules\Pacientes\Http\Requests\Ficha\StoreFichaIngresoRequest;
use App\Modules\Pacientes\Http\Requests\Ficha\UpdateFichaIngresoRequest;
use App\Modules\Pacientes\Http\Resources\FichaIngresoResource;
use App\Modules\Pacientes\Http\Resources\FichaIngresoRevisionResource;
use App\Modules\Pacientes\Http\Resources\PacienteResource;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoRevision;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Queries\Ficha\GetFichaIngresoQuery;
use App\Modules\Pacientes\Queries\Ficha\GetFichaIngresoRevisionesQuery;
use App\Modules\Pacientes\Queries\Ficha\ListFichaIngresoHistorialQuery;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Support\FichaIngresoAbilities;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Ficha de ingreso administrativa (04.03 Fase A).
 *
 * No implementa la entrevista terapéutica de 06 ni el registro clínico de 11.
 */
final class FichaIngresoController
{
    public function show(
        int $id,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        ListFichaIngresoHistorialQuery $getHistorial,
        AuditRecorderContract $audit,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $getFicha($paciente, $actor);
        $historial = $getHistorial($paciente);

        $data = $ficha === null ? null : FichaIngresoResource::for($actor, $ficha, $request);

        if ($ficha !== null) {
            $this->audit($audit, $actor, $ficha, $request, 'ficha_ingreso');
        }

        return response()->json([
            'data' => $data,
            'paciente' => PacienteResource::forList($actor, $paciente, $request),
            'historial' => $historial
                ->map(static fn (FichaIngreso $item): array => FichaIngresoResource::historial($item))
                ->values()
                ->all(),
            'can' => FichaIngresoAbilities::collection($actor),
        ]);
    }

    public function showOne(
        int $id,
        int $fichaId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        AuditRecorderContract $audit,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $getFicha($paciente, $actor, $fichaId);

        if ($ficha === null) {
            abort(404);
        }

        $data = FichaIngresoResource::for($actor, $ficha, $request);
        $this->audit($audit, $actor, $ficha, $request, 'ficha_ingreso');

        return response()->json([
            'data' => $data,
            'paciente' => PacienteResource::forList($actor, $paciente, $request),
            'can' => FichaIngresoAbilities::collection($actor),
        ]);
    }

    public function store(
        int $id,
        StoreFichaIngresoRequest $request,
        GetPacienteQuery $getPaciente,
        CreateFichaIngresoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $action($paciente, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => FichaIngresoResource::for($actor, $ficha, $request),
            'paciente' => PacienteResource::forList($actor, $paciente, $request),
            'can' => FichaIngresoAbilities::collection($actor),
        ], 201);
    }

    public function update(
        int $id,
        int $fichaId,
        UpdateFichaIngresoRequest $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        UpdateFichaIngresoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $ficha = $action($paciente, $ficha, $data, $expectedVersion, $actor, $request->ip());

        return response()->json([
            'data' => FichaIngresoResource::for($actor, $ficha, $request),
            'can' => FichaIngresoAbilities::collection($actor),
        ]);
    }

    public function complete(
        int $id,
        int $fichaId,
        FichaVersionRequest $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        CompleteFichaIngresoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $ficha = $action($paciente, $ficha, (int) $request->validated()['version'], $actor, $request->ip());

        return response()->json([
            'data' => FichaIngresoResource::for($actor, $ficha, $request),
            'can' => FichaIngresoAbilities::collection($actor),
        ]);
    }

    public function archive(
        int $id,
        int $fichaId,
        ArchiveFichaIngresoRequest $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        ArchiveFichaIngresoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $data = $request->validated();

        $ficha = $action(
            $paciente,
            $ficha,
            (string) $data['motivo'],
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => FichaIngresoResource::for($actor, $ficha, $request),
            'can' => FichaIngresoAbilities::collection($actor),
        ]);
    }

    public function reactivate(
        int $id,
        int $fichaId,
        ReactivateFichaIngresoRequest $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        ReactivateFichaIngresoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $data = $request->validated();

        $ficha = $action(
            $paciente,
            $ficha,
            (string) $data['motivo'],
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => FichaIngresoResource::for($actor, $ficha, $request),
            'can' => FichaIngresoAbilities::collection($actor),
        ]);
    }

    public function destroy(
        int $id,
        int $fichaId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        RejectFichaIngresoDeletionAction $action,
    ): never {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $action();
    }

    public function revisions(
        int $id,
        int $fichaId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        GetFichaIngresoRevisionesQuery $getRevisiones,
        AuditRecorderContract $audit,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $revisiones = $getRevisiones($ficha);

        $data = $revisiones
            ->map(static fn (FichaIngresoRevision $revision): array => FichaIngresoRevisionResource::for(
                $actor,
                $revision,
                $request,
            ))
            ->values()
            ->all();

        $this->audit($audit, $actor, $ficha, $request, 'ficha_ingreso_revisiones');

        return response()->json([
            'data' => $data,
            'can' => FichaIngresoAbilities::collection($actor),
        ]);
    }

    private function findFicha(
        GetFichaIngresoQuery $getFicha,
        Paciente $paciente,
        User $actor,
        int $fichaId,
    ): FichaIngreso {
        $ficha = $getFicha($paciente, $actor, $fichaId);

        if ($ficha === null) {
            throw new FichaIngresoNotFoundException($fichaId);
        }

        return $ficha;
    }

    private function audit(
        AuditRecorderContract $audit,
        User $actor,
        FichaIngreso $ficha,
        Request $request,
        string $recurso,
    ): void {
        $audit->registrarConsultaSensible(
            entidad: 'ficha_ingreso',
            entidadId: (int) $ficha->id,
            sucursalId: (int) $ficha->sucursal_ingreso_id,
            contexto: ['recurso' => $recurso],
            usuarioId: $actor->id,
            ip: $request->ip(),
        );
    }
}
