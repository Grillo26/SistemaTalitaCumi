<?php

namespace App\Modules\Pacientes\Http\Controllers\Registro;

use App\Models\User;
use App\Modules\Pacientes\Actions\Registro\ChangePacienteStatusAction;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use App\Modules\Pacientes\Http\Requests\Registro\ChangePacienteStatusRequest;
use App\Modules\Pacientes\Http\Resources\PacienteResource;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Support\PacienteAbilities;
use App\Modules\Pacientes\Support\PacientesPermission;
use Illuminate\Http\JsonResponse;

/**
 * Cambio de estado del paciente — módulo 04.01.
 */
final class PacienteStatusController
{
    public function changeStatus(
        int $id,
        ChangePacienteStatusRequest $request,
        GetPacienteQuery $getPaciente,
        ChangePacienteStatusAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $target = EstadoPaciente::from((string) $data['estado']);
        $permission = $target === EstadoPaciente::RETIRADO
            ? PacientesPermission::PATIENT_RETIRE
            : PacientesPermission::PATIENT_CHANGE_STATUS;

        if (! $actor->can($permission)) {
            abort(403, 'Acción no permitida para su rol.');
        }

        $paciente = $action(
            $getPaciente($id, $actor),
            $target,
            $data['motivo'] ?? null,
            (int) $data['version'],
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => PacienteResource::forFicha($actor, $paciente, $request),
            'can' => PacienteAbilities::collection($actor),
        ]);
    }
}
