<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Queries\GenerateComprobantePdfQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetComprobanteDataQuery;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Inscripción Salas Grupales] RF-012 / CA-009.
 *
 * La vista imprimible HTML está disponible; el PDF depende de
 * infraestructura y responde 503 mientras el contrato siga en stub.
 */
final class InscripcionComprobanteController
{
    public function show(int $id, Request $request, GetComprobanteDataQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => InscripcionAbilities::for($actor),
        ]);
    }

    public function pdf(int $id, Request $request, GenerateComprobantePdfQuery $query): StreamedResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return $query($id, $actor);
    }
}
