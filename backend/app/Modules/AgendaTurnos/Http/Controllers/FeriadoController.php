<?php

namespace App\Modules\AgendaTurnos\Http\Controllers;

use App\Models\User;
use App\Modules\AgendaTurnos\Actions\Feriados\CreateFeriadoAction;
use App\Modules\AgendaTurnos\Actions\Feriados\DeactivateFeriadoAction;
use App\Modules\AgendaTurnos\Actions\Feriados\UpdateFeriadoAction;
use App\Modules\AgendaTurnos\Http\Requests\IndexFeriadoRequest;
use App\Modules\AgendaTurnos\Http\Requests\StoreFeriadoRequest;
use App\Modules\AgendaTurnos\Http\Requests\UpdateFeriadoRequest;
use App\Modules\AgendaTurnos\Http\Resources\FeriadoResource;
use App\Modules\AgendaTurnos\Queries\ListFeriados;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Programación de Turnos] RF-016 / RN-008 / RT-003.
 *
 * Catálogo configurable de feriados. Sin precarga ni borrado físico: la baja
 * es lógica (`activo = false`).
 */
final class FeriadoController
{
    /**
     * @return array<string, bool>
     */
    private function can(User $actor): array
    {
        return [
            'ver' => $actor->can(AgendaTurnosPermission::VER),
            'feriados' => $actor->can(AgendaTurnosPermission::FERIADOS),
            'generar' => $actor->can(AgendaTurnosPermission::GENERAR),
            'regenerar' => $actor->can(AgendaTurnosPermission::REGENERAR),
            'asignar_profesional' => $actor->can(AgendaTurnosPermission::ASIGNAR_PROFESIONAL),
        ];
    }

    public function index(
        IndexFeriadoRequest $request,
        ListFeriados $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $feriados = $query($actor, $request->validated());

        return response()->json([
            'data' => FeriadoResource::collection($feriados)->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function store(
        StoreFeriadoRequest $request,
        CreateFeriadoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $feriado = $action(
            (string) $data['fecha'],
            (string) $data['descripcion'],
            isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => (new FeriadoResource($feriado))->resolve($request),
            'can' => $this->can($actor),
        ], 201);
    }

    public function update(
        UpdateFeriadoRequest $request,
        int $id,
        UpdateFeriadoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $feriado = $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => (new FeriadoResource($feriado))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }

    public function desactivar(
        Request $request,
        int $id,
        DeactivateFeriadoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $feriado = $action($id, $actor, $request->ip());

        return response()->json([
            'data' => (new FeriadoResource($feriado))->resolve($request),
            'can' => $this->can($actor),
        ]);
    }
}
