<?php

namespace App\Modules\CatalogoServicios\Http\Controllers;

use App\Models\User;
use App\Modules\CatalogoServicios\Actions\TiposServicio\ChangeTipoServicioStatusAction;
use App\Modules\CatalogoServicios\Actions\TiposServicio\CreateTipoServicioAction;
use App\Modules\CatalogoServicios\Actions\TiposServicio\RejectTipoServicioDeletionAction;
use App\Modules\CatalogoServicios\Actions\TiposServicio\UpdateTipoServicioAction;
use App\Modules\CatalogoServicios\Http\Requests\ChangeCatalogoStatusRequest;
use App\Modules\CatalogoServicios\Http\Requests\IndexTipoServicioRequest;
use App\Modules\CatalogoServicios\Http\Requests\StoreTipoServicioRequest;
use App\Modules\CatalogoServicios\Http\Requests\UpdateTipoServicioRequest;
use App\Modules\CatalogoServicios\Http\Resources\TipoServicioResource;
use App\Modules\CatalogoServicios\Queries\GetTipoServicioQuery;
use App\Modules\CatalogoServicios\Queries\ListTiposServicioQuery;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tipos de servicio configurables — módulo 05, submódulo 01.
 *
 * RN-C09 y RF-C05.
 */
final class TipoServicioController
{
    public function index(IndexTipoServicioRequest $request, ListTiposServicioQuery $query): JsonResponse
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
            'data' => TipoServicioResource::collection($items)->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetTipoServicioQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new TipoServicioResource($query($id)))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function store(StoreTipoServicioRequest $request, CreateTipoServicioAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tipo = $action($request->validated(), $user->id, $request->ip());

        return response()->json([
            'data' => (new TipoServicioResource($tipo))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateTipoServicioRequest $request, UpdateTipoServicioAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $tipo = $action($id, $data, $version, $user->id, $request->ip());

        return response()->json([
            'data' => (new TipoServicioResource($tipo))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function activate(int $id, ChangeCatalogoStatusRequest $request, ChangeTipoServicioStatusAction $action): JsonResponse
    {
        return $this->changeStatus($id, true, $request, $action);
    }

    public function deactivate(int $id, ChangeCatalogoStatusRequest $request, ChangeTipoServicioStatusAction $action): JsonResponse
    {
        return $this->changeStatus($id, false, $request, $action);
    }

    public function destroy(int $id, RejectTipoServicioDeletionAction $action): never
    {
        $action($id);
    }

    private function changeStatus(
        int $id,
        bool $target,
        ChangeCatalogoStatusRequest $request,
        ChangeTipoServicioStatusAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $tipo = $action(
            $id,
            $target,
            $data['motivo'],
            (int) $data['version'],
            $user->id,
            $request->ip(),
        );

        return response()->json([
            'data' => (new TipoServicioResource($tipo))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }
}
