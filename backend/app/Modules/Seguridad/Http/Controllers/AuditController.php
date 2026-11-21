<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Auditoria\RecordAuditQueryAction;
use App\Modules\Seguridad\Http\Requests\IndexAuditEntryRequest;
use App\Modules\Seguridad\Http\Requests\ReconstructAuditEntityRequest;
use App\Modules\Seguridad\Http\Resources\AuditEntryResource;
use App\Modules\Seguridad\Queries\Auditoria\GetAuditEntryQuery;
use App\Modules\Seguridad\Queries\Auditoria\GetAuditFilterOptionsQuery;
use App\Modules\Seguridad\Queries\Auditoria\GetEntitySnapshotAtDateQuery;
use App\Modules\Seguridad\Queries\Auditoria\ListAuditEntriesQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Consulta de bitácora — módulo 02, Fase 4.
 *
 * 02/RF-B03–B06, B10, B11, B14, B15.
 */
final class AuditController
{
    public function index(
        IndexAuditEntryRequest $request,
        ListAuditEntriesQuery $query,
        RecordAuditQueryAction $recordQuery,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $filters = $request->validated();

        $paginator = $query($filters, $actor);

        $recordQuery($filters, $paginator->total(), $actor, $request->ip());

        return response()->json([
            'data' => AuditEntryResource::collection($paginator->items())->resolve($request),
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
            'can' => ['export' => false],
        ]);
    }

    public function show(
        int $id,
        Request $request,
        GetAuditEntryQuery $query,
        RecordAuditQueryAction $recordQuery,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $entry = $query($id, $actor);

        $recordQuery(['id' => $id], 1, $actor, $request->ip());

        return response()->json([
            'data' => (new AuditEntryResource($entry))->resolve($request),
            'can' => ['export' => false],
        ]);
    }

    public function options(
        Request $request,
        GetAuditFilterOptionsQuery $query,
        RecordAuditQueryAction $recordQuery,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $data = $query($actor);

        $recordQuery(['tipo' => 'opciones'], 0, $actor, $request->ip());

        return response()->json([
            'data' => $data,
            'can' => ['export' => false],
        ]);
    }

    public function reconstruct(
        ReconstructAuditEntityRequest $request,
        GetEntitySnapshotAtDateQuery $query,
        RecordAuditQueryAction $recordQuery,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $snapshot = $query(
            (string) $data['entidad'],
            (int) $data['entidad_id'],
            (string) $data['fecha'],
            $actor,
        );

        $recordQuery($data, $snapshot['encontrado'] ? 1 : 0, $actor, $request->ip());

        return response()->json(['data' => $snapshot]);
    }
}
