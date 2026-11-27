<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\BajaAtencionAction;
use App\Modules\InscripcionesAtenciones\Actions\CreateDiagnosticoAction;
use App\Modules\InscripcionesAtenciones\Actions\RejectAtencionDeletionAction;
use App\Modules\InscripcionesAtenciones\Actions\UpdateDiagnosticoAction;
use App\Modules\InscripcionesAtenciones\Actions\VincularEntrevistaADiagnosticoAction;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Http\Requests\BajaAtencionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\BuscarEntrevistaPreviaRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexDiagnosticoRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\PreviewMontoDiagnosticoRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\RequisitosDiagnosticoRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\StoreDiagnosticoRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\UpdateDiagnosticoRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\VincularEntrevistaRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\DiagnosticoListResource;
use App\Modules\InscripcionesAtenciones\Http\Resources\DiagnosticoResource;
use App\Modules\InscripcionesAtenciones\Queries\BuscarEntrevistaPreviaQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetAtencionQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetRequisitosDiagnosticoQuery;
use App\Modules\InscripcionesAtenciones\Queries\ListAtencionesQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreviewMontoDiagnosticoQuery;
use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Entrevista y Diagnóstico] RF-002, RF-003, RF-004, RF-005, RF-006,
 * RF-007, RF-008, RF-009, RF-010, RF-011. RN-002 a RN-007, RN-009,
 * RN-011. CA-002, CA-003, CA-004, CA-005, CA-006, CA-007, CA-008.
 */
final class DiagnosticoAtencionController
{
    public function index(
        IndexDiagnosticoRequest $request,
        ListAtencionesQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            tipo: TipoAtencion::DIAGNOSTICO,
            filters: $data,
            actor: $actor,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'fecha_inicio'),
            direction: (string) ($data['direction'] ?? 'desc'),
        );

        return response()->json([
            'data' => DiagnosticoListResource::collection($paginator->items())->resolve($request),
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
        PreviewMontoDiagnosticoRequest $request,
        PreviewMontoDiagnosticoQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $tarifa = $query(
            tipoDiagnosticoId: (int) $data['tipo_diagnostico_id'],
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
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function requisitos(
        RequisitosDiagnosticoRequest $request,
        GetRequisitosDiagnosticoQuery $query,
    ): JsonResponse {
        return response()->json([
            'data' => $query((int) $request->validated()['tipo_diagnostico_id']),
        ]);
    }

    public function entrevistasPrevias(
        BuscarEntrevistaPreviaRequest $request,
        BuscarEntrevistaPreviaQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(
                (int) $request->validated()['paciente_id'],
                $actor,
            ),
        ]);
    }

    public function show(int $id, Request $request, GetAtencionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => (new DiagnosticoResource(
                $query(TipoAtencion::DIAGNOSTICO, $id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function store(
        StoreDiagnosticoRequest $request,
        CreateDiagnosticoAction $action,
        GetAtencionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $diagnostico = $action($request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new DiagnosticoResource(
                $query(TipoAtencion::DIAGNOSTICO, (int) $diagnostico->id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdateDiagnosticoRequest $request,
        UpdateDiagnosticoAction $action,
        GetAtencionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new DiagnosticoResource(
                $query(TipoAtencion::DIAGNOSTICO, $id, $actor),
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
        $action(TipoAtencion::DIAGNOSTICO, $id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new DiagnosticoResource(
                $query(TipoAtencion::DIAGNOSTICO, $id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function vincular(
        int $id,
        VincularEntrevistaRequest $request,
        VincularEntrevistaADiagnosticoAction $action,
        GetAtencionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $action($id, (int) $request->validated()['entrevista_id'], $actor, $request->ip());

        return response()->json([
            'data' => (new DiagnosticoResource(
                $query(TipoAtencion::DIAGNOSTICO, $id, $actor),
            ))->resolve($request),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function destroy(int $id, RejectAtencionDeletionAction $action): never
    {
        $action($id);
    }
}
