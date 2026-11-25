<?php

namespace App\Modules\Pacientes\Http\Controllers\Resumen;

use App\Models\User;
use App\Modules\Pacientes\Http\Requests\Resumen\ShowPacienteResumenRequest;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Queries\Resumen\GetPacienteResumenIndicadoresQuery;
use App\Modules\Pacientes\Queries\Resumen\GetPacienteResumenQuery;
use App\Modules\Pacientes\Support\PacienteAbilities;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Http\JsonResponse;

/**
 * Ficha resumen y refresco de indicadores (04.04 Fase B).
 *
 * La apertura se audita con `recurso=ficha_resumen`, una sola vez y sin
 * llamar a la ficha completa. El refresco de indicadores no se audita.
 */
final class PacienteResumenController
{
    public function show(
        int $id,
        ShowPacienteResumenRequest $request,
        GetPacienteQuery $getPaciente,
        GetPacienteResumenQuery $query,
        AuditRecorderContract $audit,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $sucursalId = isset($request->validated()['sucursal_id'])
            ? (int) $request->validated()['sucursal_id']
            : null;

        $data = $query($paciente, $actor, $request, $sucursalId);

        $audit->registrarConsultaSensible(
            entidad: 'paciente',
            entidadId: (int) $paciente->id,
            sucursalId: (int) $paciente->sucursal_registro_id,
            contexto: ['recurso' => 'ficha_resumen'],
            usuarioId: $actor->id,
            ip: $request->ip(),
        );

        return response()->json([
            'data' => $data,
            'can' => PacienteAbilities::collection($actor),
        ]);
    }

    public function indicators(
        int $id,
        ShowPacienteResumenRequest $request,
        GetPacienteQuery $getPaciente,
        GetPacienteResumenIndicadoresQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $getPaciente($id, $actor);
        $sucursalId = isset($request->validated()['sucursal_id'])
            ? (int) $request->validated()['sucursal_id']
            : null;

        $data = $query($paciente, $actor, $sucursalId);

        return response()->json([
            'data' => $data,
            'can' => PacienteAbilities::collection($actor),
        ]);
    }
}
