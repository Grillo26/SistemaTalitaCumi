<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Http\Resources\AreaResource;
use App\Modules\Organizacion\Queries\ListAreasQuery;
use App\Modules\Organizacion\Support\SalasAmbientesAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Áreas de solo lectura para el selector de salas.
 *
 * El CRUD y los colores pertenecen al módulo 05. Aquí solo se expone el
 * catálogo global existente.
 */
final class AreaController
{
    public function index(Request $request, ListAreasQuery $query): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'data' => AreaResource::collection($query())->resolve($request),
            'can' => SalasAmbientesAbilities::for($user),
        ]);
    }
}
