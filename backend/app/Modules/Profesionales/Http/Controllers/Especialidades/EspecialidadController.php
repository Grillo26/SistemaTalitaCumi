<?php

namespace App\Modules\Profesionales\Http\Controllers\Especialidades;

use App\Models\User;
use App\Modules\Profesionales\Actions\Especialidades\CreateEspecialidadAction;
use App\Modules\Profesionales\Actions\Especialidades\RejectEspecialidadDeletionAction;
use App\Modules\Profesionales\Actions\Especialidades\UpdateEspecialidadAction;
use App\Modules\Profesionales\Http\Requests\Especialidades\IndexEspecialidadRequest;
use App\Modules\Profesionales\Http\Requests\Especialidades\StoreEspecialidadRequest;
use App\Modules\Profesionales\Http\Requests\Especialidades\UpdateEspecialidadRequest;
use App\Modules\Profesionales\Http\Resources\EspecialidadResource;
use App\Modules\Profesionales\Models\Especialidad;
use App\Modules\Profesionales\Queries\Especialidades\GetEspecialidadQuery;
use App\Modules\Profesionales\Queries\Especialidades\ListEspecialidadesQuery;
use App\Modules\Profesionales\Support\EspecialidadAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogo de especialidades — módulo 03, Fase 1.
 *
 * 03/RF-E01, 03/RF-E02, 03/RF-E03, 03/RF-E14.
 */
final class EspecialidadController
{
    public function index(IndexEspecialidadRequest $request, ListEspecialidadesQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            estado: $data['estado'] ?? null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre'),
            direction: (string) ($data['direction'] ?? 'asc'),
        );

        $items = collect($paginator->items())
            ->map(static fn (Especialidad $especialidad): array => EspecialidadResource::forActor(
                $actor,
                $especialidad,
                $request,
            ))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
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
            'can' => EspecialidadAbilities::collection($actor),
        ]);
    }

    public function show(int $id, Request $request, GetEspecialidadQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $especialidad = $query($id);

        return response()->json([
            'data' => EspecialidadResource::forActor($actor, $especialidad, $request),
            'can' => EspecialidadAbilities::collection($actor),
        ]);
    }

    public function store(StoreEspecialidadRequest $request, CreateEspecialidadAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $especialidad = $action($request->validated(), $actor->id, $request->ip());
        $especialidad->loadCount('profesionalesActivos');

        return response()->json([
            'data' => EspecialidadResource::forActor($actor, $especialidad, $request),
            'can' => EspecialidadAbilities::collection($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdateEspecialidadRequest $request,
        UpdateEspecialidadAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $especialidad = $action($id, $data, $expectedVersion, $actor->id, $request->ip());
        $especialidad->loadCount('profesionalesActivos');

        return response()->json([
            'data' => EspecialidadResource::forActor($actor, $especialidad, $request),
            'can' => EspecialidadAbilities::collection($actor),
        ]);
    }

    /**
     * Rechaza siempre la eliminación física (03/RF-E11).
     */
    public function destroy(int $id, RejectEspecialidadDeletionAction $action): never
    {
        $action($id);
    }
}
