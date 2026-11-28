<?php

namespace App\Modules\Pagos\Http\Controllers;

use App\Models\User;
use App\Modules\Pagos\Enums\TipoEntidadPago;
use App\Modules\Pagos\Http\Requests\BuscarEntidadPagoRequest;
use App\Modules\Pagos\Queries\BuscarEntidadPagableQuery;
use App\Modules\Pagos\Queries\GetHistorialEntidadQuery;
use App\Modules\Pagos\Support\PagoAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [08.01] Búsqueda de entidades a pagar e historial por entidad (RF-006, RF-007, CU-03).
 */
final class PagoEntidadController
{
    public function buscar(BuscarEntidadPagoRequest $request, BuscarEntidadPagableQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query((string) $request->validated('q'), $actor),
            'can' => PagoAbilities::for($actor),
        ]);
    }

    public function historial(
        string $tipo,
        int $id,
        Request $request,
        GetHistorialEntidadQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(TipoEntidadPago::from($tipo), $id, $actor),
            'can' => PagoAbilities::for($actor),
        ]);
    }
}
