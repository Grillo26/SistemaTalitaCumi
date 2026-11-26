<?php

namespace App\Modules\CatalogoServicios\Http\Controllers;

use App\Models\User;
use App\Modules\CatalogoServicios\Actions\TiposDiagnostico\ChangeTipoDiagnosticoStatusAction;
use App\Modules\CatalogoServicios\Actions\TiposDiagnostico\CreateTipoDiagnosticoAction;
use App\Modules\CatalogoServicios\Actions\TiposDiagnostico\RejectTipoDiagnosticoDeletionAction;
use App\Modules\CatalogoServicios\Actions\TiposDiagnostico\UpdateTipoDiagnosticoAction;
use App\Modules\CatalogoServicios\Http\Requests\ChangeTipoDiagnosticoStatusRequest;
use App\Modules\CatalogoServicios\Http\Requests\IndexTipoDiagnosticoRequest;
use App\Modules\CatalogoServicios\Http\Requests\StoreTipoDiagnosticoRequest;
use App\Modules\CatalogoServicios\Http\Requests\UpdateTipoDiagnosticoRequest;
use App\Modules\CatalogoServicios\Http\Resources\TipoDiagnosticoResource;
use App\Modules\CatalogoServicios\Queries\GetEspecialidadesRequeridasQuery;
use App\Modules\CatalogoServicios\Queries\GetTipoDiagnosticoQuery;
use App\Modules\CatalogoServicios\Queries\ListTiposDiagnosticoQuery;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tipos de diagnóstico — módulo 05, submódulo 03.
 *
 * RF-D01 a RF-D13 (incluye RF-D12 de permisos).
 */
final class TipoDiagnosticoController
{
    public function index(
        IndexTipoDiagnosticoRequest $request,
        ListTiposDiagnosticoQuery $query,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $paginator = $query($request->validated());

        return response()->json([
            'data' => TipoDiagnosticoResource::collection($paginator->items())->resolve($request),
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

    public function show(
        int $id,
        Request $request,
        GetTipoDiagnosticoQuery $query,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => (new TipoDiagnosticoResource($query($id)))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function requirements(
        int $id,
        Request $request,
        GetEspecialidadesRequeridasQuery $query,
    ): JsonResponse {
        return response()->json([
            'data' => $query($id),
        ]);
    }

    public function store(
        StoreTipoDiagnosticoRequest $request,
        CreateTipoDiagnosticoAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $tipo = $action($request->validated(), $user->id, $request->ip());

        return response()->json([
            'data' => (new TipoDiagnosticoResource($tipo))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ], 201);
    }

    public function update(
        int $id,
        UpdateTipoDiagnosticoRequest $request,
        UpdateTipoDiagnosticoAction $action,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();
        $version = (int) $data['version'];
        unset($data['version']);

        $tipo = $action($id, $data, $version, $user->id, $request->ip());

        return response()->json([
            'data' => (new TipoDiagnosticoResource($tipo))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }

    public function activate(
        int $id,
        ChangeTipoDiagnosticoStatusRequest $request,
        ChangeTipoDiagnosticoStatusAction $action,
    ): JsonResponse {
        return $this->changeStatus($id, true, $request, $action);
    }

    public function deactivate(
        int $id,
        ChangeTipoDiagnosticoStatusRequest $request,
        ChangeTipoDiagnosticoStatusAction $action,
    ): JsonResponse {
        return $this->changeStatus($id, false, $request, $action);
    }

    public function destroy(int $id, RejectTipoDiagnosticoDeletionAction $action): never
    {
        $action($id);
    }

    private function changeStatus(
        int $id,
        bool $target,
        ChangeTipoDiagnosticoStatusRequest $request,
        ChangeTipoDiagnosticoStatusAction $action,
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
            'data' => (new TipoDiagnosticoResource($tipo))->resolve($request),
            'can' => CatalogoServiciosAbilities::for($user),
        ]);
    }
}
