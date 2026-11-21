<?php

namespace App\Modules\Profesionales\Http\Controllers\Directorio;

use App\Models\User;
use App\Modules\Profesionales\Actions\Directorio\CreateProfesionalAction;
use App\Modules\Profesionales\Actions\Directorio\RejectProfesionalDeletionAction;
use App\Modules\Profesionales\Actions\Directorio\UpdateProfesionalAction;
use App\Modules\Profesionales\Http\Requests\Directorio\IndexProfesionalRequest;
use App\Modules\Profesionales\Http\Requests\Directorio\StoreProfesionalRequest;
use App\Modules\Profesionales\Http\Requests\Directorio\UpdateProfesionalRequest;
use App\Modules\Profesionales\Http\Resources\ProfesionalResource;
use App\Modules\Profesionales\Models\Profesional;
use App\Modules\Profesionales\Queries\Directorio\GetProfesionalFormOptionsQuery;
use App\Modules\Profesionales\Queries\Directorio\GetProfesionalQuery;
use App\Modules\Profesionales\Queries\Directorio\ListProfesionalesQuery;
use App\Modules\Profesionales\Support\ProfesionalAbilities;
use App\Modules\Profesionales\Support\ProfesionalAccessScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Gestión del directorio de profesionales — módulo 03, Fase 1.
 *
 * 03/RF-D01–D07, 03/RF-D09–D14, 03/RF-E06, 03/RF-E07.
 */
final class ProfesionalController
{
    public function __construct(private readonly ProfesionalAccessScope $scope) {}

    public function index(IndexProfesionalRequest $request, ListProfesionalesQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            especialidadId: isset($data['especialidad_id']) ? (int) $data['especialidad_id'] : null,
            sucursalId: isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            estado: $data['estado'] ?? null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre_completo'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
        );

        $branchIds = $this->scope->accessibleBranchIds($actor);

        $items = collect($paginator->items())
            ->map(static fn (Profesional $profesional): array => ProfesionalResource::forActor(
                $actor,
                $profesional,
                $request,
                $branchIds,
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
            'can' => ProfesionalAbilities::collection($actor),
        ]);
    }

    public function options(Request $request, GetProfesionalFormOptionsQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($actor),
            'can' => ProfesionalAbilities::collection($actor),
        ]);
    }

    public function show(int $id, Request $request, GetProfesionalQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $profesional = $query($id, $actor);

        return response()->json([
            'data' => ProfesionalResource::forActor(
                $actor,
                $profesional,
                $request,
                $this->scope->accessibleBranchIds($actor),
            ),
            'can' => ProfesionalAbilities::collection($actor),
        ]);
    }

    public function store(StoreProfesionalRequest $request, CreateProfesionalAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $profesional = $action($request->validated(), $actor->id, $request->ip());
        $profesional->load(['relacionesEspecialidad.especialidad', 'relacionesSucursal.sucursal']);

        return response()->json([
            'data' => ProfesionalResource::forActor(
                $actor,
                $profesional,
                $request,
                $this->scope->accessibleBranchIds($actor),
            ),
            'can' => ProfesionalAbilities::collection($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdateProfesionalRequest $request,
        GetProfesionalQuery $getProfesional,
        UpdateProfesionalAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $profesional = $getProfesional($id, $actor);
        $profesional = $action($profesional, $data, $expectedVersion, $actor, $request->ip());

        return response()->json([
            'data' => ProfesionalResource::forActor(
                $actor,
                $profesional,
                $request,
                $this->scope->accessibleBranchIds($actor),
            ),
            'can' => ProfesionalAbilities::collection($actor),
        ]);
    }

    /**
     * Rechaza siempre la eliminación física (03/RF-D08).
     */
    public function destroy(
        int $id,
        Request $request,
        GetProfesionalQuery $getProfesional,
        RejectProfesionalDeletionAction $action,
    ): never {
        /** @var User $actor */
        $actor = $request->user();

        $action($getProfesional($id, $actor));
    }
}
