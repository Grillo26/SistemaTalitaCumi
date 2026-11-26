<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Enums\TipoAtencion;
use App\Modules\InscripcionesAtenciones\Queries\GetPagoAtencionQuery;
use App\Modules\InscripcionesAtenciones\Queries\IniciarPagoAtencionQuery;
use App\Modules\InscripcionesAtenciones\Support\AtencionAbilities;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [Entrevista y Diagnóstico] RF-013.
 *
 * Endpoints separados de la ficha para que el stub de Pagos no tumbe la
 * pantalla principal. Con el stub responden 503 de dominio.
 */
final class AtencionPagoController
{
    public function showEntrevista(int $id, Request $request, GetPagoAtencionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(TipoAtencion::ENTREVISTA, $id, $actor)->toArray(),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function storeEntrevista(int $id, Request $request, IniciarPagoAtencionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(TipoAtencion::ENTREVISTA, $id, $actor)->toArray(),
            'can' => AtencionAbilities::for($actor),
        ], 201);
    }

    public function showDiagnostico(int $id, Request $request, GetPagoAtencionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(TipoAtencion::DIAGNOSTICO, $id, $actor)->toArray(),
            'can' => AtencionAbilities::for($actor),
        ]);
    }

    public function storeDiagnostico(int $id, Request $request, IniciarPagoAtencionQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query(TipoAtencion::DIAGNOSTICO, $id, $actor)->toArray(),
            'can' => AtencionAbilities::for($actor),
        ], 201);
    }
}
