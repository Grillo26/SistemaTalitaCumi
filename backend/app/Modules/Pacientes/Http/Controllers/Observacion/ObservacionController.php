<?php

namespace App\Modules\Pacientes\Http\Controllers\Observacion;

use App\Models\User;
use App\Modules\Pacientes\Actions\Observacion\CreateObservacionAction;
use App\Modules\Pacientes\Actions\Observacion\RejectObservacionDeletionAction;
use App\Modules\Pacientes\Exceptions\FichaIngresoNotFoundException;
use App\Modules\Pacientes\Http\Requests\Observacion\StoreObservacionRequest;
use App\Modules\Pacientes\Http\Resources\ObservacionResource;
use App\Modules\Pacientes\Models\FichaIngreso;
use App\Modules\Pacientes\Models\FichaIngresoObservacion;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Queries\Ficha\GetFichaIngresoQuery;
use App\Modules\Pacientes\Queries\Observacion\GetObservacionQuery;
use App\Modules\Pacientes\Queries\Observacion\ListObservacionesQuery;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Support\FichaIngresoAbilities;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Observaciones fechadas de la ficha de ingreso (04.03 Fase C, RF-006).
 */
final class ObservacionController
{
    public function index(
        int $id,
        int $fichaId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        ListObservacionesQuery $getObservaciones,
        AuditRecorderContract $audit,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);

        $this->audit($audit, $actor, $ficha, $request);

        return response()->json([
            'data' => $getObservaciones($ficha)
                ->map(static fn (FichaIngresoObservacion $observacion): array => ObservacionResource::for(
                    $actor,
                    $observacion,
                ))
                ->values()
                ->all(),
            'can' => FichaIngresoAbilities::for($actor, $ficha),
        ]);
    }

    public function store(
        int $id,
        int $fichaId,
        StoreObservacionRequest $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        CreateObservacionAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $observacion = $action($ficha, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => ObservacionResource::for($actor, $observacion),
            'can' => FichaIngresoAbilities::for($actor, $ficha),
        ], 201);
    }

    public function destroy(
        int $id,
        int $fichaId,
        int $observacionId,
        Request $request,
        GetPacienteQuery $getPaciente,
        GetFichaIngresoQuery $getFicha,
        GetObservacionQuery $getObservacion,
        RejectObservacionDeletionAction $action,
    ): never {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $ficha = $this->findFicha($getFicha, $paciente, $actor, $fichaId);
        $getObservacion($ficha, $observacionId);
        $action();
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
    ): void {
        $audit->registrarConsultaSensible(
            entidad: 'ficha_ingreso',
            entidadId: (int) $ficha->id,
            sucursalId: (int) $ficha->sucursal_ingreso_id,
            contexto: ['recurso' => 'ficha_ingreso_observaciones'],
            usuarioId: $actor->id,
            ip: $request->ip(),
        );
    }
}
