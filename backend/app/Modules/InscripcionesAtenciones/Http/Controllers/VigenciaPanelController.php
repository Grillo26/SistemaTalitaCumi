<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexVigenciaRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\VigenciaRowResource;
use App\Modules\InscripcionesAtenciones\Queries\GetPanelVigenciasQuery;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;
use Illuminate\Http\JsonResponse;

/**
 * [Vigencia y Renovación] RF-004 / CA-003 / CA-011 / RNF-008.
 */
final class VigenciaPanelController
{
    public function index(
        IndexVigenciaRequest $request,
        GetPanelVigenciasQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            filters: $data,
            actor: $actor,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 20),
            sort: (string) ($data['sort'] ?? 'fecha_fin'),
            direction: (string) ($data['direction'] ?? 'asc'),
        );

        return response()->json([
            'data' => VigenciaRowResource::collection($paginator->items())->resolve($request),
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
            'can' => VigenciaAbilities::for($actor),
        ]);
    }
}
