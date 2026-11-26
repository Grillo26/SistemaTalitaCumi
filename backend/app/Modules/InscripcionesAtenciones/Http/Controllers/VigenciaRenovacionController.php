<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\RenovarDesdePanelAction;
use App\Modules\InscripcionesAtenciones\Actions\RenovarMultipleAction;
use App\Modules\InscripcionesAtenciones\Enums\TipoEntidadVigencia;
use App\Modules\InscripcionesAtenciones\Http\Requests\RenovarMultipleRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\RenovarVigenciaRequest;
use App\Modules\InscripcionesAtenciones\Queries\PreviewRenovacionQuery;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Vigencia y Renovación] RF-005 / RF-006 / RF-016 / CU-02.
 *
 * La renovación se delega en las Actions de Salas y Paquetes. Este controller
 * solo normaliza la entrada/salida del panel.
 */
final class VigenciaRenovacionController
{
    public function preview(
        string $tipo,
        int $id,
        Request $request,
        PreviewRenovacionQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $entityType = TipoEntidadVigencia::from($tipo);

        return response()->json([
            'data' => $query($entityType, $id, $actor),
            'can' => VigenciaAbilities::for($actor),
        ]);
    }

    public function renew(
        string $tipo,
        int $id,
        RenovarVigenciaRequest $request,
        RenovarDesdePanelAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $result = $action(
            TipoEntidadVigencia::from($tipo),
            $id,
            $request->validated(),
            $actor,
            $request->ip(),
        );

        return response()->json([
            'data' => $result->toArray(),
            'can' => VigenciaAbilities::for($actor),
        ], 201);
    }

    public function multiple(
        RenovarMultipleRequest $request,
        RenovarMultipleAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $action($request->validated(), $actor, $request->ip()),
            'can' => VigenciaAbilities::for($actor),
        ], 201);
    }
}
