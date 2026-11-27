<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GetPagoPaqueteQuery;
use App\Modules\InscripcionesAtenciones\Queries\IniciarPagoPaqueteQuery;
use App\Modules\InscripcionesAtenciones\Support\PaqueteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Paquetes Sesiones Individuales] RF-016. Endpoints separados de la ficha.
 */
final class PaquetePagoController
{
    public function show(int $id, Request $request, GetPagoPaqueteQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function store(int $id, Request $request, IniciarPagoPaqueteQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => PaqueteAbilities::for($actor),
        ], 201);
    }
}
