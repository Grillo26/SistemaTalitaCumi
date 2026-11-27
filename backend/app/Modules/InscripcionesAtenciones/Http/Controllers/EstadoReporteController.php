<?php

namespace App\Modules\InscripcionesAtenciones\Http\Controllers;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Http\Requests\ExportEstadoRequest;
use App\Modules\InscripcionesAtenciones\Http\Requests\ReporteEstadoRequest;
use App\Modules\InscripcionesAtenciones\Http\Resources\EstadoInscripcionRowResource;
use App\Modules\InscripcionesAtenciones\Queries\ExportEstadosQuery;
use App\Modules\InscripcionesAtenciones\Queries\ReporteEstadosQuery;
use App\Modules\InscripcionesAtenciones\Support\EstadoAbilities;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Estados Paciente e Inscripción] RF-012 / RNF-009 / CA-010.
 */
final class EstadoReporteController
{
    public function index(
        ReporteEstadoRequest $request,
        ReporteEstadosQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $reporte = $query(
            filters: $data,
            actor: $actor,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 20),
            sort: (string) ($data['sort'] ?? 'fecha_fin'),
            direction: (string) ($data['direction'] ?? 'asc'),
        );

        if ($reporte['tipo'] === 'inscripciones') {
            $reporte['data'] = EstadoInscripcionRowResource::collection($reporte['data'])->resolve($request);
        }

        return response()->json([
            'data' => $reporte,
            'can' => EstadoAbilities::for($actor),
        ]);
    }

    public function export(
        ExportEstadoRequest $request,
        ExportEstadosQuery $query,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $query($request->validated(), $actor);
    }
}
