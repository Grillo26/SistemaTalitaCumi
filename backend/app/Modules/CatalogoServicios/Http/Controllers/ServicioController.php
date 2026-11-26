<?php

namespace App\Modules\CatalogoServicios\Http\Controllers;

use App\Models\User;
use App\Modules\CatalogoServicios\Actions\Servicios\ChangeServicioStatusAction;
use App\Modules\CatalogoServicios\Actions\Servicios\CreateServicioAction;
use App\Modules\CatalogoServicios\Actions\Servicios\RejectServicioDeletionAction;
use App\Modules\CatalogoServicios\Actions\Servicios\UpdateServicioAction;
use App\Modules\CatalogoServicios\Http\Requests\ChangeCatalogoStatusRequest;
use App\Modules\CatalogoServicios\Http\Requests\IndexServicioRequest;
use App\Modules\CatalogoServicios\Http\Requests\StoreServicioRequest;
use App\Modules\CatalogoServicios\Http\Requests\UpdateServicioRequest;
use App\Modules\CatalogoServicios\Http\Resources\ServicioResource;
use App\Modules\CatalogoServicios\Queries\GetServicioQuery;
use App\Modules\CatalogoServicios\Queries\ListServiciosQuery;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Servicios del catálogo — módulo 05, submódulo 01.
 *
 * RF-C05 a RF-C08, RF-C10 a RF-C14 y RF-C16.
 */
final class ServicioController
{
    public function index(IndexServicioRequest $request, ListServiciosQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $paginator = $query($request->validated());

        return response()->json([
            'data' => ServicioResource::collection($paginator->items())->resolve($request),
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetServicioQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new ServicioResource($query($id)))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function store(StoreServicioRequest $request, CreateServicioAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $servicio = $action($request->validated(), $user->id, $request->ip());

        return response()->json([
            'data' => (new ServicioResource($servicio))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ], 201);
    }

    public function update(int $id, UpdateServicioRequest $request, UpdateServicioAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $servicio = $action($id, $data, $version, $user->id, $request->ip());

        return response()->json([
            'data' => (new ServicioResource($servicio))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function activate(int $id, ChangeCatalogoStatusRequest $request, ChangeServicioStatusAction $action): JsonResponse
    {
        return $this->changeStatus($id, true, $request, $action);
    }

    public function deactivate(int $id, ChangeCatalogoStatusRequest $request, ChangeServicioStatusAction $action): JsonResponse
    {
        return $this->changeStatus($id, false, $request, $action);
    }

    public function destroy(int $id, RejectServicioDeletionAction $action): never
    {
        $action($id);
    }

    private function changeStatus(
        int $id,
        bool $target,
        ChangeCatalogoStatusRequest $request,
        ChangeServicioStatusAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $servicio = $action(
            $id,
            $target,
            $data['motivo'],
            (int) $data['version'],
            $user->id,
            $request->ip(),
        );

        return response()->json([
            'data' => (new ServicioResource($servicio))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }
}
