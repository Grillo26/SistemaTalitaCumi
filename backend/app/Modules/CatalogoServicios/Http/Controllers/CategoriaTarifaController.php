<?php

namespace App\Modules\CatalogoServicios\Http\Controllers;

use App\Models\User;
use App\Modules\CatalogoServicios\Actions\CategoriasTarifa\ChangeCategoriaTarifaStatusAction;
use App\Modules\CatalogoServicios\Actions\CategoriasTarifa\CreateCategoriaTarifaAction;
use App\Modules\CatalogoServicios\Actions\CategoriasTarifa\RejectCategoriaTarifaDeletionAction;
use App\Modules\CatalogoServicios\Actions\CategoriasTarifa\UpdateCategoriaTarifaAction;
use App\Modules\CatalogoServicios\Http\Requests\ChangeCategoriaTarifaStatusRequest;
use App\Modules\CatalogoServicios\Http\Requests\IndexCategoriaTarifaRequest;
use App\Modules\CatalogoServicios\Http\Requests\StoreCategoriaTarifaRequest;
use App\Modules\CatalogoServicios\Http\Requests\UpdateCategoriaTarifaRequest;
use App\Modules\CatalogoServicios\Http\Resources\CategoriaTarifaResource;
use App\Modules\CatalogoServicios\Queries\GetCategoriaTarifaQuery;
use App\Modules\CatalogoServicios\Queries\ListCategoriasTarifaQuery;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Categorías de tarifa — módulo 05, submódulo 02.
 *
 * RF-T01, RF-T02, RF-T09, RF-T15 y RF-T17.
 */
final class CategoriaTarifaController
{
    public function index(
        IndexCategoriaTarifaRequest $request,
        ListCategoriasTarifaQuery $query,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $items = $query(
            $data['search'] ?? null,
            $data['estado'] ?? null,
            $data['sort'] ?? 'nombre',
            $data['direction'] ?? 'asc',
        );

        return response()->json([
            'data' => CategoriaTarifaResource::collection($items)->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function show(
        int $id,
        Request $request,
        GetCategoriaTarifaQuery $query,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new CategoriaTarifaResource($query($id)))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function store(
        StoreCategoriaTarifaRequest $request,
        CreateCategoriaTarifaAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $categoria = $action($request->validated(), $user->id, $request->ip());

        return response()->json([
            'data' => (new CategoriaTarifaResource($categoria))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ], 201);
    }

    public function update(
        int $id,
        UpdateCategoriaTarifaRequest $request,
        UpdateCategoriaTarifaAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $categoria = $action($id, $data, $version, $user->id, $request->ip());

        return response()->json([
            'data' => (new CategoriaTarifaResource($categoria))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function activate(
        int $id,
        ChangeCategoriaTarifaStatusRequest $request,
        ChangeCategoriaTarifaStatusAction $action,
    ): JsonResponse {
        return $this->changeStatus($id, true, $request, $action);
    }

    public function deactivate(
        int $id,
        ChangeCategoriaTarifaStatusRequest $request,
        ChangeCategoriaTarifaStatusAction $action,
    ): JsonResponse {
        return $this->changeStatus($id, false, $request, $action);
    }

    public function destroy(int $id, RejectCategoriaTarifaDeletionAction $action): never
    {
        $action($id);
    }

    private function changeStatus(
        int $id,
        bool $target,
        ChangeCategoriaTarifaStatusRequest $request,
        ChangeCategoriaTarifaStatusAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $categoria = $action(
            $id,
            $target,
            $data['motivo'] ?? null,
            (int) $data['version'],
            $user->id,
            $request->ip(),
        );

        return response()->json([
            'data' => (new CategoriaTarifaResource($categoria))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }
}
