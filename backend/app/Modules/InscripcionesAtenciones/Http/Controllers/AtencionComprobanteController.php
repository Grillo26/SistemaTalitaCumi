<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Queries\GenerateComprobanteAtencionPdfQuery;
use App\Modules\InscripcionesAtenciones\Queries\GetAtencionComprobanteDataQuery;
use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Entrevista y Diagnóstico] RF-012 / CA-010.
 *
 * La vista imprimible HTML está disponible; el PDF responde 503 mientras
 * el contrato siga en stub.
 */
final class AtencionComprobanteController
{
    public function showEntrevista(
        int $id,
        Request $request,
        GetAtencionComprobanteDataQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(TipoAtencion::ENTREVISTA, $id, $actor),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function showDiagnostico(
        int $id,
        Request $request,
        GetAtencionComprobanteDataQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(TipoAtencion::DIAGNOSTICO, $id, $actor),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function pdfEntrevista(
        int $id,
        Request $request,
        GenerateComprobanteAtencionPdfQuery $query,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $query(TipoAtencion::ENTREVISTA, $id, $actor);
    }

    public function pdfDiagnostico(
        int $id,
        Request $request,
        GenerateComprobanteAtencionPdfQuery $query,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $query(TipoAtencion::DIAGNOSTICO, $id, $actor);
    }
}
