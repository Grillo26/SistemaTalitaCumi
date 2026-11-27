<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\BajaPaqueteAction;
use App\Modules\InscripcionesAtenciones\Actions\CreatePaqueteSesionesAction;
use App\Modules\InscripcionesAtenciones\Actions\RejectPaqueteDeletionAction;
use App\Modules\InscripcionesAtenciones\Actions\RenovarPaqueteAction;
use App\Modules\InscripcionesAtenciones\Actions\UpdatePaqueteAction;
use App\Modules\InscripcionesAtenciones\Http\Requests\BajaPaqueteRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexPaqueteRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\PreviewMontoPaqueteRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\RenovarPaqueteRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\StorePaqueteRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\UpdatePaqueteRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\PaqueteListResource;
use App\Modules\InscripcionesAtenciones\Http\Resources\PaqueteResource;
use App\Modules\InscripcionesAtenciones\Models\PaqueteSesiones;
use App\Modules\InscripcionesAtenciones\Queries\GetPaqueteOpcionesQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetPaqueteQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetPaqueteRenovacionPreviewQuery;
use App\Modules\InscripcionesAtenciones\Queries\ListPaquetesQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreviewMontoPaqueteQuery;
use App\Modules\InscripcionesAtenciones\Support\PaqueteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Paquetes Sesiones Individuales] RF-001 a RF-014, RF-017.
 * RN-001 a RN-003, RN-006, RN-008, RN-010, RN-011.
 */
final class PaqueteSesionesController
{
    public function index(IndexPaqueteRequest $request, ListPaquetesQuery $query): JsonResponse
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
            'data' => PaqueteListResource::collection($paginator->items())->resolve($request),
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
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function opciones(Request $request, GetPaqueteOpcionesQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function previewMonto(
        PreviewMontoPaqueteRequest $request,
        PreviewMontoPaqueteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $query(
                servicioId: (int) $data['servicio_id'],
                sucursalId: (int) $data['sucursal_id'],
                categoriaTarifaId: (int) $data['categoria_tarifa_id'],
                fechaInicio: (string) $data['fecha_inicio'],
                cantidadSesiones: isset($data['cantidad_sesiones'])
                    ? (int) $data['cantidad_sesiones']
                    : null,
                actor: $actor,
                diasSemana: isset($data['dias_semana']) ? (array) $data['dias_semana'] : null,
                fechaFin: isset($data['fecha_fin']) ? (string) $data['fecha_fin'] : null,
            ),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function show(int $id, Request $request, GetPaqueteQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new PaqueteResource($query($id, $actor)))->resolve($request),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function renovacionPreview(
        int $id,
        Request $request,
        GetPaqueteRenovacionPreviewQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function store(
        StorePaqueteRequest $request,
        CreatePaqueteSesionesAction $action,
        GetPaqueteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paquete = $action($request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new PaqueteResource($query((int) $paquete->id, $actor)))->resolve($request),
            'can' => PaqueteAbilities::for($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdatePaqueteRequest $request,
        UpdatePaqueteAction $action,
        GetPaqueteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new PaqueteResource($query($id, $actor)))->resolve($request),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function renew(
        int $id,
        RenovarPaqueteRequest $request,
        RenovarPaqueteAction $action,
        GetPaqueteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        /** @var PaqueteSesiones $nuevo */
        $nuevo = $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new PaqueteResource($query((int) $nuevo->id, $actor)))->resolve($request),
            'paquete_origen_id' => $id,
            'can' => PaqueteAbilities::for($actor),
        ], 201);
    }

    public function withdraw(
        int $id,
        BajaPaqueteRequest $request,
        BajaPaqueteAction $action,
        GetPaqueteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new PaqueteResource($query($id, $actor)))->resolve($request),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function destroy(int $id, RejectPaqueteDeletionAction $action): never
    {
        $action($id);
    }
}
