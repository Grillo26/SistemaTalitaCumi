<?php

namespace App\Modules\AgendaTurnos\Http\Controllers\Cambios;

use App\Models\User;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\ExportarCambiosRequest;
use App\Modules\AgendaTurnos\Http\Requests\Cambios\ReporteCambiosRequest;
use App\Modules\AgendaTurnos\Http\Resources\Cambios\ReporteCambioResource;
use App\Modules\AgendaTurnos\Queries\Cambios\ExportCambiosTurnos;
use App\Modules\AgendaTurnos\Queries\Cambios\ReporteCambiosTurnos;
use App\Modules\AgendaTurnos\Support\CambiosTurnosPermission;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * [Reprogramación y Cancelación] RF-012 / RNF-010.
 */
final class ReporteCambiosController
{
    public function index(
        ReporteCambiosRequest $request,
        ReporteCambiosTurnos $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $reporte = $query(
            $data,
            $actor,
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 20),
        );

        return response()->json([
            'data' => ReporteCambioResource::collection($reporte['data'])->resolve($request),
            'meta' => $reporte['meta'],
            'conteos' => $reporte['conteos'],
            'filtros' => $reporte['filtros'],
            'can' => [
                'reporte' => $actor->can(CambiosTurnosPermission::REPORTE),
                'exportar' => $actor->can(CambiosTurnosPermission::EXPORTAR),
            ],
        ]);
    }

    public function export(
        ExportarCambiosRequest $request,
        ExportCambiosTurnos $query,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();

        return $query($request->validated(), $actor);
    }
}
