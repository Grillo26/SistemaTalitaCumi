<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GetOpcionesEstadoQuery;
use App\Modules\InscripcionesAtenciones\Support\EstadoAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Opciones de estados, acciones y motivos.
 */
final class EstadoOpcionesController
{
    public function __invoke(Request $request, GetOpcionesEstadoQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(),
            'can' => EstadoAbilities::for($actor),
        ]);
    }
}
