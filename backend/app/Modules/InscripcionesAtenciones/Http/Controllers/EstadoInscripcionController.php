<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\PausarInscripcionAction;
use App\Modules\InscripcionesAtenciones\Actions\ReactivarInscripcionAction;
use App\Modules\InscripcionesAtenciones\Actions\RetirarInscripcionAction;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexEstadoInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\PausarInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\ReactivarInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\RetirarInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\EstadoInscripcionRowResource;
use App\Modules\InscripcionesAtenciones\Queries\GetHistorialEstadosQuery;
use App\Modules\InscripcionesAtenciones\Queries\ListInscripcionesPorEstadoQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreChequeoEstadoQuery;
use App\Modules\InscripcionesAtenciones\Support\EstadoAbilities;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Estados Paciente e Inscripción] RF-004 / RF-008 / RF-011 / RF-016.
 */
final class EstadoInscripcionController
{
    public function index(
        IndexEstadoInscripcionRequest $request,
        ListInscripcionesPorEstadoQuery $query,
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
            'data' => EstadoInscripcionRowResource::collection($paginator->items())->resolve($request),
            'meta' => $this->meta($paginator),
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function historial(
        string $tipo,
        int $id,
        Request $request,
        GetHistorialEstadosQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entityType = TipoEntidadVigencia::from($tipo);

        return response()->json([
            'data' => $query->inscripcion($entityType, $id, $actor),
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function pausar(
        string $tipo,
        int $id,
        PausarInscripcionRequest $request,
        PausarInscripcionAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entityType = TipoEntidadVigencia::from($tipo);
        $estado = $action($entityType, $id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => ['estado' => $estado->value, 'estado_label' => $estado->label()],
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function reactivar(
        string $tipo,
        int $id,
        ReactivarInscripcionRequest $request,
        ReactivarInscripcionAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entityType = TipoEntidadVigencia::from($tipo);
        $resultado = $action($entityType, $id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => [
                'estado_efectivo' => $resultado['estado_efectivo']->value,
                'estado_efectivo_label' => $resultado['estado_efectivo']->label(),
            ],
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function retirar(
        string $tipo,
        int $id,
        RetirarInscripcionRequest $request,
        RetirarInscripcionAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entityType = TipoEntidadVigencia::from($tipo);
        $entidad = $action($entityType, $id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => [
                'id' => (int) $entidad->id,
                'estado' => $entidad->estado->value,
                'estado_label' => $entidad->estado->label(),
            ],
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function prechequeoPausa(
        string $tipo,
        int $id,
        Request $request,
        PreChequeoEstadoQuery $query,
    ): JsonResponse {
        return $this->prechequeo($tipo, $id, 'pausar', $request, $query);
    }

    public function prechequeoRetiro(
        string $tipo,
        int $id,
        Request $request,
        PreChequeoEstadoQuery $query,
    ): JsonResponse {
        return $this->prechequeo($tipo, $id, 'retirar', $request, $query);
    }

    public function prechequeoReactivacion(
        string $tipo,
        int $id,
        Request $request,
        PreChequeoEstadoQuery $query,
    ): JsonResponse {
        return $this->prechequeo($tipo, $id, 'reactivar', $request, $query);
    }

    private function prechequeo(
        string $tipo,
        int $id,
        string $accion,
        Request $request,
        PreChequeoEstadoQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entityType = TipoEntidadVigencia::from($tipo);

        return response()->json([
            'data' => $query->inscripcion($entityType, $id, $accion, $actor),
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function meta(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
