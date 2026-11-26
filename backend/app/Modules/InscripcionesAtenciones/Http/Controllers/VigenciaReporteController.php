<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Http\Requests\ExportVigenciaRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexVigenciaRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\VigenciaRowResource;
use App\Modules\InscripcionesAtenciones\Queries\ExportVigenciasQuery;
use App\Modules\InscripcionesAtenciones\Queries\ListVencimientosReporteQuery;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Vigencia y Renovación] RF-008 / RF-009 / RF-010 / CA-008 / CA-009 / CA-010.
 */
final class VigenciaReporteController
{
    public function index(
        IndexVigenciaRequest $request,
        ListVencimientosReporteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            filters: $data,
            actor: $actor,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 25),
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

    public function export(
        ExportVigenciaRequest $request,
        ExportVigenciasQuery $query,
    ): StreamedResponse|JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $query($request->validated(), $actor);
    }
}
