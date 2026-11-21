<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Actions\RestoreParameterDefaultAction;
use App\Modules\Organizacion\Actions\UpdateParameterAction;
use App\Modules\Organizacion\Actions\UploadCenterLogoAction;
use App\Modules\Organizacion\Http\Requests\IndexParameterHistoryRequest;
use App\Modules\Organizacion\Http\Requests\IndexParameterRequest;
use App\Modules\Organizacion\Http\Requests\RestoreParameterRequest;
use App\Modules\Organizacion\Http\Requests\UpdateParameterRequest;
use App\Modules\Organizacion\Http\Requests\UploadCenterLogoRequest;
use App\Modules\Organizacion\Http\Resources\ParameterHistoryResource;
use App\Modules\Organizacion\Http\Resources\ParameterResource;
use App\Modules\Organizacion\Queries\GetParameterHistoryQuery;
use App\Modules\Organizacion\Queries\GetParameterQuery;
use App\Modules\Organizacion\Queries\ListParametersQuery;
use App\Modules\Organizacion\Support\ConfiguracionAbilities;
use App\Modules\Organizacion\Support\ConfiguracionPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Configuración general y parámetros — Submódulo 03.
 *
 * RF-001, RF-002, RF-003, RF-004, RF-005, RF-006, RF-007, RF-008, RF-009,
 * RF-010, RF-011, RF-012, RF-013, RF-014 y RF-016.
 * RN aplicadas: RN-001 a RN-011. RF-015 queda pendiente de infraestructura.
 */
final class ParameterController
{
    public function index(IndexParameterRequest $request, ListParametersQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $onlyVisible = ! $user->can(ConfiguracionPermission::VIEW);

        $items = $query(
            search: $data['search'] ?? null,
            categoria: $data['categoria'] ?? null,
            onlyVisible: $onlyVisible,
        );

        return response()->json([
            'data' => ParameterResource::collection($items)->resolve($request),
            'can' => ConfiguracionAbilities::for($user),
        ]);
    }

    public function show(int $id, Request $request, GetParameterQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $onlyVisible = ! $user->can(ConfiguracionPermission::VIEW);

        return response()->json([
            'data' => (new ParameterResource($query($id, $onlyVisible)))->resolve($request),
            'can' => ConfiguracionAbilities::for($user),
        ]);
    }

    public function update(int $id, UpdateParameterRequest $request, UpdateParameterAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $parametro = $action($id, $data, $version, $user->id);

        return response()->json([
            'data' => (new ParameterResource($parametro))->resolve($request),
            'can' => ConfiguracionAbilities::for($user),
        ]);
    }

    public function restore(int $id, RestoreParameterRequest $request, RestoreParameterDefaultAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $parametro = $action(
            $id,
            (int) $data['version'],
            filter_var($data['confirmado'] ?? false, FILTER_VALIDATE_BOOLEAN),
            $data['motivo'] ?? null,
            $user->id,
        );

        return response()->json([
            'data' => (new ParameterResource($parametro))->resolve($request),
            'can' => ConfiguracionAbilities::for($user),
        ]);
    }

    public function history(int $id, IndexParameterHistoryRequest $request, GetParameterHistoryQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $onlyVisible = ! $user->can(ConfiguracionPermission::VIEW);

        $paginator = $query(
            $id,
            $onlyVisible,
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 10),
        );

        return response()->json([
            'data' => ParameterHistoryResource::collection($paginator->items())->resolve($request),
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
            'can' => ConfiguracionAbilities::for($user),
        ]);
    }

    public function uploadLogo(UploadCenterLogoRequest $request, UploadCenterLogoAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $parametro = $action(
            $request->file('logo'),
            (int) $data['version'],
            $data['motivo'] ?? null,
            $user->id,
        );

        return response()->json([
            'data' => (new ParameterResource($parametro))->resolve($request),
            'can' => ConfiguracionAbilities::for($user),
        ]);
    }
}
