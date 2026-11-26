<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\BajaInscripcionAction;
use App\Modules\InscripcionesAtenciones\Actions\CreateInscripcionSalaGrupalAction;
use App\Modules\InscripcionesAtenciones\Actions\RejectInscripcionDeletionAction;
use App\Modules\InscripcionesAtenciones\Actions\RenovarInscripcionAction;
use App\Modules\InscripcionesAtenciones\Actions\UpdateInscripcionAction;
use App\Modules\InscripcionesAtenciones\Http\Requests\BajaInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\PreviewMontoEsperadoRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\RenovarInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\StoreInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\UpdateInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\InscripcionListResource;
use App\Modules\InscripcionesAtenciones\Http\Resources\InscripcionResource;
use App\Modules\InscripcionesAtenciones\Models\InscripcionSala;
use App\Modules\InscripcionesAtenciones\Queries\GetInscripcionQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetRenovacionPreviewQuery;
use App\Modules\InscripcionesAtenciones\Queries\ListInscripcionesQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreviewMontoEsperadoQuery;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Inscripción Salas Grupales] RF-001 a RF-012, RF-016.
 * RN-001, RN-002, RN-003, RN-004, RN-007, RN-008, RN-009, RN-011.
 * CA-001 a CA-009.
 */
final class InscripcionSalaGrupalController
{
    public function index(IndexInscripcionRequest $request, ListInscripcionesQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            filters: $data,
            actor: $actor,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'fecha_inicio'),
            direction: (string) ($data['direction'] ?? 'desc'),
        );

        return response()->json([
            'data' => InscripcionListResource::collection($paginator->items())->resolve($request),
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
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function previewMonto(
        PreviewMontoEsperadoRequest $request,
        PreviewMontoEsperadoQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $tarifa = $query(
            servicioId: (int) $data['servicio_id'],
            sucursalId: (int) $data['sucursal_id'],
            categoriaTarifaId: (int) $data['categoria_tarifa_id'],
            fechaInicio: (string) $data['fecha_inicio'],
            actor: $actor,
        );

        return response()->json([
            'data' => $tarifa?->toArray(),
            'tarifa_disponible' => $tarifa !== null,
            'message' => $tarifa === null
                ? 'No hay una tarifa vigente para el servicio, sucursal, categoría y fecha seleccionados.'
                : null,
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function show(int $id, Request $request, GetInscripcionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new InscripcionResource($query($id, $actor)))->resolve($request),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function renovacionPreview(
        int $id,
        Request $request,
        GetRenovacionPreviewQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function store(
        StoreInscripcionRequest $request,
        CreateInscripcionSalaGrupalAction $action,
        GetInscripcionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $inscripcion = $action($request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new InscripcionResource($query((int) $inscripcion->id, $actor)))->resolve($request),
            'can' => InscripcionAbilities::for($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdateInscripcionRequest $request,
        UpdateInscripcionAction $action,
        GetInscripcionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new InscripcionResource($query($id, $actor)))->resolve($request),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function renew(
        int $id,
        RenovarInscripcionRequest $request,
        RenovarInscripcionAction $action,
        GetInscripcionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        /** @var InscripcionSala $nueva */
        $nueva = $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new InscripcionResource($query((int) $nueva->id, $actor)))->resolve($request),
            'inscripcion_origen_id' => $id,
            'can' => InscripcionAbilities::for($actor),
        ], 201);
    }

    public function withdraw(
        int $id,
        BajaInscripcionRequest $request,
        BajaInscripcionAction $action,
        GetInscripcionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new InscripcionResource($query($id, $actor)))->resolve($request),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function destroy(int $id, RejectInscripcionDeletionAction $action): never
    {
        $action($id);
    }
}
