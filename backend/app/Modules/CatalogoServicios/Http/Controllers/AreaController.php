<?php

namespace App\Modules\CatalogoServicios\Http\Controllers;

use App\Models\User;
use App\Modules\CatalogoServicios\Actions\Areas\ChangeAreaStatusAction;
use App\Modules\CatalogoServicios\Actions\Areas\CreateAreaAction;
use App\Modules\CatalogoServicios\Actions\Areas\RejectAreaDeletionAction;
use App\Modules\CatalogoServicios\Actions\Areas\UpdateAreaAction;
use App\Modules\CatalogoServicios\Http\Requests\ChangeCatalogoStatusRequest;
use App\Modules\CatalogoServicios\Http\Requests\IndexAreaRequest;
use App\Modules\CatalogoServicios\Http\Requests\StoreAreaRequest;
use App\Modules\CatalogoServicios\Http\Requests\UpdateAreaRequest;
use App\Modules\CatalogoServicios\Http\Resources\AreaResource;
use App\Modules\CatalogoServicios\Queries\GetAreaQuery;
use App\Modules\CatalogoServicios\Queries\ListAreasQuery;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Áreas del catálogo — módulo 05, submódulo 01.
 *
 * RF-C01, RF-C02, RF-C03, RF-C04, RF-C09, RF-C14 y RF-C16.
 * El controller es delgado: Request → Action | Query → Resource.
 */
final class AreaController
{
    public function index(IndexAreaRequest $request, ListAreasQuery $query): JsonResponse
    {
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
            'data' => AreaResource::collection($items)->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetAreaQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new AreaResource($query($id)))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function store(StoreAreaRequest $request, CreateAreaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $area = $action($request->validated(), $user->id, $request->ip());

        return response()->json([
            'data' => (new AreaResource($area))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateAreaRequest $request, UpdateAreaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $area = $action($id, $data, $version, $user->id, $request->ip());

        return response()->json([
            'data' => (new AreaResource($area))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function activate(int $id, ChangeCatalogoStatusRequest $request, ChangeAreaStatusAction $action): JsonResponse
    {
        return $this->changeStatus($id, true, $request, $action);
    }

    public function deactivate(int $id, ChangeCatalogoStatusRequest $request, ChangeAreaStatusAction $action): JsonResponse
    {
        return $this->changeStatus($id, false, $request, $action);
    }

    public function destroy(int $id, RejectAreaDeletionAction $action): never
    {
        $action($id);
    }

    private function changeStatus(
        int $id,
        bool $target,
        ChangeCatalogoStatusRequest $request,
        ChangeAreaStatusAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $area = $action(
            $id,
            $target,
            $data['motivo'],
            (int) $data['version'],
            $user->id,
            $request->ip(),
        );

        return response()->json([
            'data' => (new AreaResource($area))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }
}
