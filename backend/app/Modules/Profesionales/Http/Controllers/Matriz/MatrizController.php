<?php

namespace App\Modules\Profesionales\Http\Controllers\Matriz;

use App\Models\User;
use App\Modules\Profesionales\Http\Requests\Matriz\IndexMatrizRequest;
use App\Modules\Profesionales\Http\Resources\MatrizRowResource;
use App\Modules\Profesionales\Queries\Matriz\GetMatrizFilterOptionsQuery;
use App\Modules\Profesionales\Queries\Matriz\ListMatrizQuery;
use App\Modules\Profesionales\Support\ProfesionalesPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consulta de la matriz profesional–especialidad–sucursal (03/RF-E08).
 *
 * Es solo lectura: la administración de la relación con sucursal se hace desde
 * la ficha del profesional. La exportación queda pendiente de infraestructura.
 */
final class MatrizController
{
    public function index(IndexMatrizRequest $request, ListMatrizQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            profesionalId: isset($data['profesional_id']) ? (int) $data['profesional_id'] : null,
            especialidadId: isset($data['especialidad_id']) ? (int) $data['especialidad_id'] : null,
            sucursalId: isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            estado: $data['estado'] ?? null,
            soloAsignables: filter_var($data['solo_asignables'] ?? false, FILTER_VALIDATE_BOOLEAN),
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'profesional_nombre'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
        );

        $items = collect($paginator->items())
            ->map(static fn ($row): array => (new MatrizRowResource($row))->resolve($request))
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
            'can' => [
                'export' => false,
                'view_professionals' => $actor->can(ProfesionalesPermission::PROFESSIONAL_VIEW),
            ],
        ]);
    }

    public function options(Request $request, GetMatrizFilterOptionsQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($actor),
            'can' => [
                'export' => false,
            ],
        ]);
    }
}
