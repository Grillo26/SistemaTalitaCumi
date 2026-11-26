<?php

namespace App\Modules\CatalogoServicios\Http\Controllers;

use App\Models\User;
use App\Modules\CatalogoServicios\Actions\Tarifas\CloseTarifaVigenciaAction;
use App\Modules\CatalogoServicios\Actions\Tarifas\CreateTarifaAction;
use App\Modules\CatalogoServicios\Actions\Tarifas\RejectTarifaDeletionAction;
use App\Modules\CatalogoServicios\Actions\Tarifas\UpdateTarifaAction;
use App\Modules\CatalogoServicios\Http\Requests\CloseTarifaVigenciaRequest;
use App\Modules\CatalogoServicios\Http\Requests\IndexTarifaRequest;
use App\Modules\CatalogoServicios\Http\Requests\ResolveTarifaAplicableRequest;
use App\Modules\CatalogoServicios\Http\Requests\StoreTarifaRequest;
use App\Modules\CatalogoServicios\Http\Requests\UpdateTarifaRequest;
use App\Modules\CatalogoServicios\Http\Resources\TarifaAplicableResource;
use App\Modules\CatalogoServicios\Http\Resources\TarifaResource;
use App\Modules\CatalogoServicios\Queries\GetTarifaQuery;
use App\Modules\CatalogoServicios\Queries\ListTarifasQuery;
use App\Modules\CatalogoServicios\Queries\ResolveTarifaAplicableQuery;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAbilities;
use App\Modules\CatalogoServicios\Support\TarifaAccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tarifas y vigencias — módulo 05, submódulo 02.
 *
 * RF-T03 a RF-T13, RF-T15 a RF-T17.
 */
final class TarifaController
{
    public function index(IndexTarifaRequest $request, ListTarifasQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $paginator = $query($request->validated(), $user);

        return response()->json([
            'data' => TarifaResource::collection($paginator->items())->resolve($request),
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

    public function aplicable(
        ResolveTarifaAplicableRequest $request,
        ResolveTarifaAplicableQuery $query,
        TarifaAccessScope $scope,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $scope->assertBranchAllowed($user, (int) $data['sucursal_id']);

        $resumen = $query(
            (int) $data['servicio_id'],
            (int) $data['sucursal_id'],
            (int) $data['categoria_tarifa_id'],
            (string) $data['fecha'],
        );

        return response()->json([
            'data' => $resumen === null
                ? null
                : (new TarifaAplicableResource($resumen))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function show(
        int $id,
        Request $request,
        GetTarifaQuery $query,
        TarifaAccessScope $scope,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $tarifa = $query($id);
        $scope->assertCanView($user, $tarifa);

        return response()->json([
            'data' => (new TarifaResource($tarifa))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function store(StoreTarifaRequest $request, CreateTarifaAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tarifa = $action($request->validated(), $user, $request->ip());

        return response()->json([
            'data' => (new TarifaResource($tarifa))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ], 201);
    }

    public function update(
        int $id,
        UpdateTarifaRequest $request,
        UpdateTarifaAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $tarifa = $action($id, $data, $version, $user, $request->ip());

        return response()->json([
            'data' => (new TarifaResource($tarifa))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function close(
        int $id,
        CloseTarifaVigenciaRequest $request,
        CloseTarifaVigenciaAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $tarifa = $action(
            $id,
            (string) $data['vigente_hasta'],
            (string) $data['motivo'],
            (int) $data['version'],
            $user,
            $request->ip(),
        );

        return response()->json([
            'data' => (new TarifaResource($tarifa))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function destroy(int $id, RejectTarifaDeletionAction $action): never
    {
        $action($id);
    }
}
