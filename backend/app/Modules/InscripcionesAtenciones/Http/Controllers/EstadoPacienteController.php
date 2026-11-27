<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\CambiarEstadoPacienteAction;
use App\Modules\InscripcionesAtenciones\Actions\RetirarPacienteConCascadaAction;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Http\Requests\CambiarEstadoPacienteRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexEstadoPacienteRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\RetirarPacienteRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\EstadoPacienteRowResource;
use App\Modules\InscripcionesAtenciones\Queries\GetHistorialEstadosQuery;
use App\Modules\InscripcionesAtenciones\Queries\ListPacientesPorEstadoQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreChequeoEstadoQuery;
use App\Modules\InscripcionesAtenciones\Queries\PreviewRetiroPacienteQuery;
use App\Modules\InscripcionesAtenciones\Support\EstadoAbilities;
use App\Modules\Pacientes\Enums\EstadoPaciente;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Estados Paciente e Inscripción] RF-003 / RF-005 / RF-010 / RF-011.
 */
final class EstadoPacienteController
{
    public function index(
        IndexEstadoPacienteRequest $request,
        ListPacientesPorEstadoQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $paginator = $query(
            filters: $data,
            actor: $actor,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 20),
            sort: (string) ($data['sort'] ?? 'nombre_completo'),
            direction: (string) ($data['direction'] ?? 'asc'),
        );

        return response()->json([
            'data' => EstadoPacienteRowResource::collection($paginator->items())->resolve($request),
            'meta' => $this->meta($paginator),
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function historial(
        int $id,
        Request $request,
        GetHistorialEstadosQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query->paciente($id, $actor),
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function pausar(
        int $id,
        CambiarEstadoPacienteRequest $request,
        CambiarEstadoPacienteAction $action,
    ): JsonResponse {
        return $this->cambiar($id, EstadoPaciente::EN_PAUSA, $request, $action);
    }

    public function reactivar(
        int $id,
        CambiarEstadoPacienteRequest $request,
        CambiarEstadoPacienteAction $action,
    ): JsonResponse {
        return $this->cambiar($id, EstadoPaciente::ACTIVO, $request, $action);
    }

    public function retirar(
        int $id,
        RetirarPacienteRequest $request,
        RetirarPacienteConCascadaAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $resultado = $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => [
                'paciente' => $resultado['paciente']->toArray(),
                'inscripciones' => $resultado['inscripciones'],
            ],
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function previewRetiro(
        int $id,
        Request $request,
        PreviewRetiroPacienteQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function prechequeoRetiro(
        int $id,
        Request $request,
        PreviewRetiroPacienteQuery $preview,
        PreChequeoEstadoQuery $precheck,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $preview($id, $actor);
        $inscripciones = array_map(
            static fn (array $item): array => [
                'tipo' => TipoEntidadVigencia::from((string) $item['tipo_entidad']),
                'id' => (int) $item['id'],
                'sucursal_id' => (int) $item['sucursal']['id'],
            ],
            $data['inscripciones'],
        );

        return response()->json([
            'data' => [
                'preview' => $data,
                'prechequeo' => $precheck->paciente($inscripciones, $actor),
            ],
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    private function cambiar(
        int $id,
        EstadoPaciente $target,
        CambiarEstadoPacienteRequest $request,
        CambiarEstadoPacienteAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $action($id, $target, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new EstadoPacienteRowResource($paciente))->resolve($request),
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
