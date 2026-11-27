<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\BajaAtencionAction;
use App\Modules\InscripcionesAtenciones\Actions\CreateEntrevistaAction;
use App\Modules\InscripcionesAtenciones\Actions\RejectAtencionDeletionAction;
use App\Modules\InscripcionesAtenciones\Actions\UpdateEntrevistaAction;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Http\Requests\BajaAtencionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexEntrevistaRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\PreviewMontoEntrevistaRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\StoreEntrevistaRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\UpdateEntrevistaRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\EntrevistaListResource;
use App\Modules\InscripcionesAtenciones\Http\Resources\EntrevistaResource;
use App\Modules\InscripcionesAtenciones\Queries\GetAtencionQuery;
use App\Modules\InscripcionesAtenciones\Queries\ListAtencionesQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreviewMontoEntrevistaQuery;
use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Entrevista y Diagnóstico] RF-001, RF-006, RF-007, RF-008, RF-009,
 * RF-010, RF-011. RN-007, RN-009, RN-011. CA-001, CA-006, CA-007.
 */
final class EntrevistaAtencionController
{
    public function index(
        IndexEntrevistaRequest $request,
        ListAtencionesQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            tipo: TipoAtencion::ENTREVISTA,
            filters: $data,
            actor: $actor,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'fecha'),
            direction: (string) ($data['direction'] ?? 'desc'),
        );

        return response()->json([
            'data' => EntrevistaListResource::collection($paginator->items())->resolve($request),
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
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function previewMonto(
        PreviewMontoEntrevistaRequest $request,
        PreviewMontoEntrevistaQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $tarifa = $query(
            servicioId: (int) $data['servicio_id'],
            sucursalId: (int) $data['sucursal_id'],
            categoriaTarifaId: (int) $data['categoria_tarifa_id'],
            fecha: (string) $data['fecha'],
            actor: $actor,
        );

        return response()->json([
            'data' => $tarifa?->toArray(),
            'tarifa_disponible' => $tarifa !== null,
            'message' => $tarifa === null
                ? 'No hay una tarifa vigente para el servicio, sucursal, categoría y fecha seleccionados.'
                : null,
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function show(int $id, Request $request, GetAtencionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new EntrevistaResource(
                $query(TipoAtencion::ENTREVISTA, $id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function store(
        StoreEntrevistaRequest $request,
        CreateEntrevistaAction $action,
        GetAtencionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entrevista = $action($request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new EntrevistaResource(
                $query(TipoAtencion::ENTREVISTA, (int) $entrevista->id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdateEntrevistaRequest $request,
        UpdateEntrevistaAction $action,
        GetAtencionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new EntrevistaResource(
                $query(TipoAtencion::ENTREVISTA, $id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function withdraw(
        int $id,
        BajaAtencionRequest $request,
        BajaAtencionAction $action,
        GetAtencionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action(TipoAtencion::ENTREVISTA, $id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new EntrevistaResource(
                $query(TipoAtencion::ENTREVISTA, $id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function destroy(int $id, RejectAtencionDeletionAction $action): never
    {
        $action($id);
    }
}
