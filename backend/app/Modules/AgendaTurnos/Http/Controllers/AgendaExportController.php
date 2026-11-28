<?php

namespace App\Modules\AgendaTurnos\Http\Controllers;

use App\Models\User;
use App\Modules\AgendaTurnos\Contracts\AgendaExporterContract;
use App\Modules\AgendaTurnos\Http\Requests\IndexAgendaSemanalRequest;
use App\Modules\AgendaTurnos\Queries\GetAgendaExportData;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Agenda Semanal] RF-010, RN-008.
 *
 * Sin infraestructura PDF el contrato lanza una excepción de dominio 503; no
 * se devuelve un archivo vacío ni simulado.
 */
final class AgendaExportController
{
    public function export(
        IndexAgendaSemanalRequest $request,
        GetAgendaExportData $query,
        AgendaExporterContract $exporter,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();
        $datos = $query($actor, $request->validated());

        return $exporter->exportar($datos, $actor->id);
    }
}
