<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GetPagoInscripcionQuery;
use App\Modules\InscripcionesAtenciones\Queries\IniciarPagoInscripcionQuery;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Inscripción Salas Grupales] RF-013 / CA-010.
 *
 * Endpoints separados de la ficha para que el stub de Pagos no tumbe la
 * ficha principal. Con el stub responden 503 de dominio.
 */
final class InscripcionPagoController
{
    public function show(int $id, Request $request, GetPagoInscripcionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor)->toArray(),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function store(int $id, Request $request, IniciarPagoInscripcionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor)->toArray(),
            'can' => InscripcionAbilities::for($actor),
        ], 201);
    }
}
