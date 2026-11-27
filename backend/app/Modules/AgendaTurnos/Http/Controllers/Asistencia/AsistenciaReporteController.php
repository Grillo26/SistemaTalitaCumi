<?php

namespace App\Modules\AgendaTurnos\Http\Controllers\Asistencia;

use App\Models\User;
use App\Modules\AgendaTurnos\Contracts\AsistenciaPdfExporterContract;
use App\Modules\AgendaTurnos\Http\Requests\Asistencia\ExportarAsistenciaRequest;
use App\Modules\AgendaTurnos\Http\Requests\Asistencia\IndicadoresAsistenciaRequest;
use App\Modules\AgendaTurnos\Http\Requests\Asistencia\ResumenDiarioRequest;
use App\Modules\AgendaTurnos\Queries\Asistencia\ExportarAsistenciaCsv;
use App\Modules\AgendaTurnos\Queries\Asistencia\GetIndicadoresAsistencia;
use App\Modules\AgendaTurnos\Queries\Asistencia\GetResumenDiarioAsistencia;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosPermission;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 07.04/RF-009, 07.04/RF-010, 07.04/RF-014.
 *
 * Indicadores, resumen diario y exportación. El PDF responde 503 hasta que
 * exista infraestructura; el CSV es compatible con Excel.
 */
final class AsistenciaReporteController
{
    public function indicadores(
        IndicadoresAsistenciaRequest $request,
        GetIndicadoresAsistencia $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $query(
                $data,
                $actor,
                (int) ($data['page'] ?? 1),
                (int) ($data['per_page'] ?? 25),
            ),
            'can' => $this->can($actor),
        ]);
    }

    public function resumenDiario(
        ResumenDiarioRequest $request,
        GetResumenDiarioAsistencia $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $query(
                (string) $data['fecha'],
                (int) $data['sucursal_id'],
                $actor,
            ),
            'can' => $this->can($actor),
        ]);
    }

    public function exportar(
        ExportarAsistenciaRequest $request,
        ExportarAsistenciaCsv $csv,
        AsistenciaPdfExporterContract $pdf,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $formato = (string) ($data['formato'] ?? 'csv');

        if ($formato === 'pdf') {
            return $pdf->exportar($data, (int) $actor->id);
        }

        return $csv($data, $actor, $request->ip());
    }

    /**
     * @return array<string, bool>
     */
    private function can(User $actor): array
    {
        return [
            'ver_indicadores' => $actor->can(AsistenciaTurnosPermission::VER_INDICADORES),
            'resumen_diario' => $actor->can(AsistenciaTurnosPermission::RESUMEN_DIARIO),
            'exportar' => $actor->can(AsistenciaTurnosPermission::EXPORTAR),
        ];
    }
}
