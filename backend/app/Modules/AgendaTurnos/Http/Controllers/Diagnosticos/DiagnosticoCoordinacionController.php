<?php

namespace App\Modules\AgendaTurnos\Http\Controllers\Diagnosticos;

use App\Models\User;
use App\Modules\AgendaTurnos\Actions\Diagnosticos\ActualizarEstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Actions\Diagnosticos\CancelarDiagnostico;
use App\Modules\AgendaTurnos\Actions\Diagnosticos\ProgramarTurnoDiagnostico;
use App\Modules\AgendaTurnos\Contracts\DiagnosticosPdfExporterContract;
use App\Modules\AgendaTurnos\Enums\EstadoGeneralDiagnostico;
use App\Modules\AgendaTurnos\Http\Requests\Diagnosticos\ActualizarEstadoGeneralRequest;
use App\Modules\AgendaTurnos\Http\Requests\Diagnosticos\CancelarDiagnosticoRequest;
use App\Modules\AgendaTurnos\Http\Requests\Diagnosticos\ExportarDiagnosticosRequest;
use App\Modules\AgendaTurnos\Http\Requests\Diagnosticos\IndexDiagnosticosRequest;
use App\Modules\AgendaTurnos\Http\Requests\Diagnosticos\OpcionesDiagnosticosRequest;
use App\Modules\AgendaTurnos\Http\Requests\Diagnosticos\ProgramarTurnoDiagnosticoRequest;
use App\Modules\AgendaTurnos\Queries\Diagnosticos\ExportarDiagnosticosCsv;
use App\Modules\AgendaTurnos\Queries\Diagnosticos\GetCoordinacionDiagnostico;
use App\Modules\AgendaTurnos\Queries\Diagnosticos\GetOpcionesDiagnosticos;
use App\Modules\AgendaTurnos\Queries\Diagnosticos\GetWhatsappDiagnostico;
use App\Modules\AgendaTurnos\Queries\Diagnosticos\ListDiagnosticosCoordinacion;
use App\Modules\AgendaTurnos\Support\DiagnosticosPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 07.05/RF-001 a RF-014.
 *
 * Controller delgado: valida formato, delega en Action/Query y devuelve JSON
 * o el stream CSV. No hay DELETE: la cancelación es un cambio de estado.
 */
final class DiagnosticoCoordinacionController
{
    public function index(
        IndexDiagnosticosRequest $request,
        ListDiagnosticosCoordinacion $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json($query($request->validated(), $actor));
    }

    public function opciones(
        OpcionesDiagnosticosRequest $request,
        GetOpcionesDiagnosticos $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json($query(
            $actor,
            isset($data['sucursal_id']) ? (int) $data['sucursal_id'] : null,
        ));
    }

    public function show(
        int $id,
        Request $request,
        GetCoordinacionDiagnostico $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json($query($id, $actor, $request->ip()));
    }

    public function programar(
        int $id,
        ProgramarTurnoDiagnosticoRequest $request,
        ProgramarTurnoDiagnostico $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $action(
                $id,
                (int) $data['profesional_id'],
                (string) $data['fecha'],
                (string) $data['hora_inicio'],
                isset($data['duracion_minutos']) ? (int) $data['duracion_minutos'] : null,
                $actor,
                $request->ip(),
            ),
            'can' => $this->can($actor),
        ], 201);
    }

    public function actualizarEstado(
        int $id,
        ActualizarEstadoGeneralRequest $request,
        ActualizarEstadoGeneralDiagnostico $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $action(
                $id,
                EstadoGeneralDiagnostico::from((string) $data['estado_general']),
                (string) $data['motivo'],
                $actor,
                $request->ip(),
            ),
            'can' => $this->can($actor),
        ]);
    }

    public function cancelar(
        int $id,
        CancelarDiagnosticoRequest $request,
        CancelarDiagnostico $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        return response()->json([
            'data' => $action($id, (string) $data['motivo'], $actor, $request->ip()),
            'can' => $this->can($actor),
        ]);
    }

    public function whatsapp(
        int $id,
        Request $request,
        GetWhatsappDiagnostico $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor, $request->ip()),
        ]);
    }

    public function exportar(
        ExportarDiagnosticosRequest $request,
        ExportarDiagnosticosCsv $csv,
        DiagnosticosPdfExporterContract $pdf,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        if (($data['formato'] ?? 'csv') === 'pdf') {
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
            'ver' => $actor->can(DiagnosticosPermission::VER),
            'programar' => $actor->can(DiagnosticosPermission::PROGRAMAR),
            'cancelar' => $actor->can(DiagnosticosPermission::CANCELAR),
            'actualizar_estado' => $actor->can(DiagnosticosPermission::ACTUALIZAR_ESTADO),
            'exportar' => $actor->can(DiagnosticosPermission::EXPORTAR),
            'whatsapp' => $actor->can(DiagnosticosPermission::WHATSAPP),
        ];
    }
}
