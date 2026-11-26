<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GetConsumoPaqueteQuery;
use App\Modules\InscripcionesAtenciones\Support\PaqueteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Paquetes Sesiones Individuales] RF-009. Endpoint separado de la ficha.
 */
final class PaqueteConsumoController
{
    public function show(int $id, Request $request, GetConsumoPaqueteQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }
}
