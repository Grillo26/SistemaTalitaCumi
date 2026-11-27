<?php

namespace App\Modules\AgendaTurnos\Http\Controllers;

use App\Models\User;
use App\Modules\AgendaTurnos\Http\Requests\IndexAgendaSemanalRequest;
use App\Modules\AgendaTurnos\Http\Requests\IndexOpcionesFiltrosRequest;
use App\Modules\AgendaTurnos\Http\Resources\AgendaSemanalResource;
use App\Modules\AgendaTurnos\Http\Resources\OpcionesFiltrosResource;
use App\Modules\AgendaTurnos\Http\Resources\SucursalDisponibleResource;
use App\Modules\AgendaTurnos\Queries\GetAgendaSemana;
use App\Modules\AgendaTurnos\Queries\GetOpcionesFiltros;
use App\Modules\AgendaTurnos\Queries\GetSucursalesDisponibles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Agenda Semanal] RF-001, RF-002, RF-005, RF-011, RF-013, RF-014.
 * RN-001, RN-004, RN-005, RN-006.
 * CA-001, CA-002, CA-003, CA-004, CA-005, CA-007.
 */
final class AgendaSemanalController
{
    public function index(
        IndexAgendaSemanalRequest $request,
        GetAgendaSemana $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $query($actor, $request->validated());

        return response()->json([
            'data' => (new AgendaSemanalResource($result['data']))->resolve($request),
            'can' => $result['can'],
            'filtros' => $result['filtros'],
        ]);
    }

    public function opciones(
        IndexOpcionesFiltrosRequest $request,
        GetOpcionesFiltros $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $result = $query(
            $actor,
            isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
        );

        return response()->json([
            'data' => (new OpcionesFiltrosResource($result['data']))->resolve($request),
            'disponibilidad' => $result['disponibilidad'],
            'can' => $result['can'],
        ]);
    }

    public function sucursales(
        Request $request,
        GetSucursalesDisponibles $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $query($actor);

        return response()->json([
            'data' => SucursalDisponibleResource::collection($result['data'])->resolve($request),
            'predeterminada_id' => $result['predeterminada_id'],
            'can' => $result['can'],
            'zona_horaria' => $result['zona_horaria'],
        ]);
    }
}
