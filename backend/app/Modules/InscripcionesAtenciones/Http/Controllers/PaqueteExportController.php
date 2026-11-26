<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Http\Requests\IndexPaqueteRequest;
use App\Modules\InscripcionesAtenciones\Queries\ExportPaquetesQuery;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Paquetes Sesiones Individuales] RF-015 (exportación).
 */
final class PaqueteExportController
{
    public function export(
        IndexPaqueteRequest $request,
        ExportPaquetesQuery $query,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $query($request->validated(), $actor);
    }
}
