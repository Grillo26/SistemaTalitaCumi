<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GetAsistenciaInscripcionQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetTurnosFuturosQuery;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Inscripción Salas Grupales] RF-014 y pre-chequeo CU-03/FE-01.
 *
 * Endpoints separados de la ficha; con los stubs de Agenda responden 503.
 */
final class InscripcionAsistenciaController
{
    public function show(int $id, Request $request, GetAsistenciaInscripcionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor)->toArray(),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function turnosFuturos(int $id, Request $request, GetTurnosFuturosQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor)->toArray(),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }
}
