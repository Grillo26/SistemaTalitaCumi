<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Actions\UpdateVentanaAlertaAction;
use App\Modules\InscripcionesAtenciones\Http\Requests\UpdateVentanaAlertaRequest;
use App\Modules\InscripcionesAtenciones\Queries\GetVentanaAlertaQuery;
use App\Modules\InscripcionesAtenciones\Support\VigenciaAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Vigencia y Renovación] RF-003 / RN-007 / RNF-011.
 */
final class VigenciaConfiguracionController
{
    public function show(Request $request, GetVentanaAlertaQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(),
            'can' => VigenciaAbilities::for($actor),
        ]);
    }

    public function update(
        UpdateVentanaAlertaRequest $request,
        UpdateVentanaAlertaAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $action($request->validated(), $actor),
            'can' => VigenciaAbilities::for($actor),
        ]);
    }
}
