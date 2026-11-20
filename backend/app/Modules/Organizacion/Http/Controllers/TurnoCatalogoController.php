<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Actions\ActivateTurnoCatalogoAction;
use App\Modules\Organizacion\Actions\CreateTurnoCatalogoAction;
use App\Modules\Organizacion\Actions\DeactivateTurnoCatalogoAction;
use App\Modules\Organizacion\Actions\RejectTurnoCatalogoDeletionAction;
use App\Modules\Organizacion\Actions\UpdateTurnoCatalogoAction;
use App\Modules\Organizacion\Http\Requests\ChangeSpaceStatusRequest;
use App\Modules\Organizacion\Http\Requests\IndexTurnoCatalogoRequest;
use App\Modules\Organizacion\Http\Requests\StoreTurnoCatalogoRequest;
use App\Modules\Organizacion\Http\Requests\UpdateTurnoCatalogoRequest;
use App\Modules\Organizacion\Http\Resources\TurnoCatalogoResource;
use App\Modules\Organizacion\Queries\GetTurnoCatalogoQuery;
use App\Modules\Organizacion\Queries\ListTurnosCatalogoQuery;
use App\Modules\Organizacion\Support\SalasAmbientesAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Turnos operativos — Submódulo 02 Salas y ambientes.
 *
 * RF-003, RF-008, RF-009, RF-015 y RF-017 (permisos).
 */
final class TurnoCatalogoController
{
    public function index(IndexTurnoCatalogoRequest $request, ListTurnosCatalogoQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $items = $query($data['search'] ?? null, $data['estado'] ?? null);

        return response()->json([
            'data' => TurnoCatalogoResource::collection($items)->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetTurnoCatalogoQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new TurnoCatalogoResource($query($id)))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function store(StoreTurnoCatalogoRequest $request, CreateTurnoCatalogoAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $turno = $action($request->validated(), $user->id);

        return response()->json([
            'data' => (new TurnoCatalogoResource($turno))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateTurnoCatalogoRequest $request, UpdateTurnoCatalogoAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $turno = $action($id, $data, $version, $user->id);

        return response()->json([
            'data' => (new TurnoCatalogoResource($turno))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function activate(int $id, ChangeSpaceStatusRequest $request, ActivateTurnoCatalogoAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $turno = $action($id, (int) $data['version'], $user->id);

        return response()->json([
            'data' => (new TurnoCatalogoResource($turno))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function deactivate(int $id, ChangeSpaceStatusRequest $request, DeactivateTurnoCatalogoAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $turno = $action($id, $data['motivo'] ?? null, (int) $data['version'], $user->id);

        return response()->json([
            'data' => (new TurnoCatalogoResource($turno))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function destroy(int $id, RejectTurnoCatalogoDeletionAction $action): never
    {
        $action($id);
    }
}
