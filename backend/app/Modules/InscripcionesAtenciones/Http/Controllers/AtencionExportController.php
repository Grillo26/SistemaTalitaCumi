<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\AtencionExporterContract;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Entrevista y Diagnóstico] RF-014 / RNF-010.
 *
 * La infraestructura PDF/Excel no está instalada. El contrato responde 503
 * con un mensaje claro en lugar de generar un archivo vacío o simulado.
 */
final class AtencionExportController
{
    public function entrevistas(
        Request $request,
        AtencionExporterContract $exporter,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $exporter->exportar($request->query(), $actor->id);
    }

    public function diagnosticos(
        Request $request,
        AtencionExporterContract $exporter,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $exporter->exportar($request->query(), $actor->id);
    }
}
