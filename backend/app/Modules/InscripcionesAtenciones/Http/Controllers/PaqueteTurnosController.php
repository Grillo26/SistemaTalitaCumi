<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GetTurnosFuturosPaqueteQuery;
use App\Modules\InscripcionesAtenciones\Support\PaqueteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Paquetes Sesiones Individuales] Pre-chequeo CU-03/FE-01 y RF-012.
 */
final class PaqueteTurnosController
{
    public function show(int $id, Request $request, GetTurnosFuturosPaqueteQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }
}
