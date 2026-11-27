<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GeneratePaqueteComprobantePdfQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetPaqueteComprobanteDataQuery;
use App\Modules\InscripcionesAtenciones\Support\PaqueteAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Paquetes Sesiones Individuales] RF-015 / CA-010.
 */
final class PaqueteComprobanteController
{
    public function show(int $id, Request $request, GetPaqueteComprobanteDataQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => PaqueteAbilities::for($actor),
        ]);
    }

    public function pdf(int $id, Request $request, GeneratePaqueteComprobantePdfQuery $query): StreamedResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return $query($id, $actor);
    }
}
