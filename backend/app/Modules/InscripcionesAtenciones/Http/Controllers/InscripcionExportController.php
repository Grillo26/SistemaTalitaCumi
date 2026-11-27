<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexInscripcionRequest;
use App\Modules\InscripcionesAtenciones\Queries\ExportInscripcionesQuery;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Inscripción Salas Grupales] RF-015.
 *
 * Pendiente de infraestructura PDF/Excel; el contrato responde 503.
 */
final class InscripcionExportController
{
    public function export(
        IndexInscripcionRequest $request,
        ExportInscripcionesQuery $query,
    ): StreamedResponse|JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $query($request->validated(), $actor);
    }
}
