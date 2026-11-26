<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Http\Resources\VigenciaRowResource;
use App\Modules\InscripcionesAtenciones\Queries\GetResumenVigenciaPacienteQuery;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Vigencia y Renovación] RF-015 / CU-03 FE-01.
 */
final class VigenciaPacienteController
{
    public function show(
        int $pacienteId,
        Request $request,
        GetResumenVigenciaPacienteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $query($pacienteId, $actor);

        return response()->json([
            'data' => [
                'paciente' => $data['paciente'],
                'conteos' => $data['conteos'],
                'items' => VigenciaRowResource::collection($data['items'])->resolve($request),
            ],
            'can' => VigenciaAbilities::for($actor),
        ]);
    }
}
