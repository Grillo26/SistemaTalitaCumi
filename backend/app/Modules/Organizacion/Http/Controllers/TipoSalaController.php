<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Actions\ActivateTipoSalaAction;
use App\Modules\Organizacion\Actions\CreateTipoSalaAction;
use App\Modules\Organizacion\Actions\DeactivateTipoSalaAction;
use App\Modules\Organizacion\Actions\RejectTipoSalaDeletionAction;
use App\Modules\Organizacion\Actions\UpdateTipoSalaAction;
use App\Modules\Organizacion\Http\Requests\ChangeSpaceStatusRequest;
use App\Modules\Organizacion\Http\Requests\IndexTipoSalaRequest;
use App\Modules\Organizacion\Http\Requests\StoreTipoSalaRequest;
use App\Modules\Organizacion\Http\Requests\UpdateTipoSalaRequest;
use App\Modules\Organizacion\Http\Resources\TipoSalaResource;
use App\Modules\Organizacion\Queries\GetTipoSalaQuery;
use App\Modules\Organizacion\Queries\ListTiposSalaQuery;
use App\Modules\Organizacion\Support\SalasAmbientesAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tipos de sala — Submódulo 02 Salas y ambientes.
 *
 * RF-001, RF-002, RF-008, RF-009, RF-015 y RF-017 (permisos).
 */
final class TipoSalaController
{
    public function index(IndexTipoSalaRequest $request, ListTiposSalaQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $items = $query($data['search'] ?? null, $data['estado'] ?? null);

        return response()->json([
            'data' => TipoSalaResource::collection($items)->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetTipoSalaQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new TipoSalaResource($query($id)))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function store(StoreTipoSalaRequest $request, CreateTipoSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tipoSala = $action($request->validated(), $user->id);

        return response()->json([
            'data' => (new TipoSalaResource($tipoSala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateTipoSalaRequest $request, UpdateTipoSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $tipoSala = $action($id, $data, $version, $user->id);

        return response()->json([
            'data' => (new TipoSalaResource($tipoSala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function activate(int $id, ChangeSpaceStatusRequest $request, ActivateTipoSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $tipoSala = $action($id, (int) $data['version'], $user->id);

        return response()->json([
            'data' => (new TipoSalaResource($tipoSala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function deactivate(int $id, ChangeSpaceStatusRequest $request, DeactivateTipoSalaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $tipoSala = $action($id, $data['motivo'] ?? null, (int) $data['version'], $user->id);

        return response()->json([
            'data' => (new TipoSalaResource($tipoSala))->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }

    public function destroy(int $id, RejectTipoSalaDeletionAction $action): never
    {
        $action($id);
    }
}
