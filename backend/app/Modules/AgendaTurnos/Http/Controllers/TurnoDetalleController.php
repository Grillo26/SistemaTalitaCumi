<?php

namespace App\Modules\AgendaTurnos\Http\Controllers;

use App\Models\User;
use App\Modules\AgendaTurnos\Http\Resources\TurnoDetalleResource;
use App\Modules\AgendaTurnos\Queries\GetTurnoDetalle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Agenda Semanal] RF-008, RF-009, CU-03.
 */
final class TurnoDetalleController
{
    public function show(
        int $id,
        Request $request,
        GetTurnoDetalle $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $query($id, $actor);

        return response()->json([
            'data' => (new TurnoDetalleResource($result['data']))->resolve($request),
            'acciones' => $result['acciones'],
            'acciones_disponibles' => $result['acciones_disponibles'],
            'mensaje_acciones' => $result['mensaje_acciones'],
            'can' => $result['can'],
        ]);
    }
}
