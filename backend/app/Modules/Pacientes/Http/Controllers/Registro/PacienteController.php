<?php

namespace App\Modules\Pacientes\Http\Controllers\Registro;

use App\Models\User;
use App\Modules\Pacientes\Actions\Registro\CreatePacienteAction;
use App\Modules\Pacientes\Actions\Registro\RejectPacienteDeletionAction;
use App\Modules\Pacientes\Actions\Registro\UpdatePacienteAction;
use App\Modules\Pacientes\Exceptions\PacienteExportTooLargeException;
use App\Modules\Pacientes\Http\Requests\Registro\ExportPacienteRequest;
use App\Modules\Pacientes\Http\Requests\Registro\IndexPacienteRequest;
use App\Modules\Pacientes\Http\Requests\Registro\StorePacienteRequest;
use App\Modules\Pacientes\Http\Requests\Registro\UpdatePacienteRequest;
use App\Modules\Pacientes\Http\Resources\PacienteResource;
use App\Modules\Pacientes\Models\Paciente;
use App\Modules\Pacientes\Queries\Registro\GetPacienteFormOptionsQuery;
use App\Modules\Pacientes\Queries\Registro\GetPacienteQuery;
use App\Modules\Pacientes\Queries\Registro\ListPacientesQuery;
use App\Modules\Pacientes\Support\PacienteAbilities;
use App\Modules\Pacientes\Support\PacienteCsvExporter;
use App\Modules\Pacientes\Support\PacientesPermission;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Registro de pacientes — módulo 04.01.
 *
 * 04.01/RF-001–RF-009, RF-011–RF-014.
 */
final class PacienteController
{
    public function index(
        IndexPacienteRequest $request,
        ListPacientesQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            estado: $data['estado'] ?? null,
            tipoDiagnosticoId: isset($data['tipo_diagnostico_id']) ? (int) $data['tipo_diagnostico_id'] : null,
            sinDiagnostico: array_key_exists('sin_diagnostico', $data) ? (bool) $data['sin_diagnostico'] : null,
            sucursalRegistroId: isset($data['sucursal_registro_id']) ? (int) $data['sucursal_registro_id'] : null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre_completo'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
            puedeBuscarTelefonoTutor: $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT),
        );

        $items = collect($paginator->items())
            ->map(static fn (Paciente $paciente): array => PacienteResource::forList($actor, $paciente, $request))
            ->values()
            ->all();

        return response()->json([
            'data' => $items,
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'can' => PacienteAbilities::collection($actor),
        ]);
    }

    public function export(
        ExportPacienteRequest $request,
        ListPacientesQuery $query,
        PacienteCsvExporter $exporter,
        AuditRecorderContract $audit,
    ): StreamedResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $sucursalRegistroId = isset($data['sucursal_registro_id'])
            ? (int) $data['sucursal_registro_id']
            : null;

        $builder = $query->builder(
            search: $data['search'] ?? null,
            estado: $data['estado'] ?? null,
            tipoDiagnosticoId: isset($data['tipo_diagnostico_id']) ? (int) $data['tipo_diagnostico_id'] : null,
            sinDiagnostico: array_key_exists('sin_diagnostico', $data) ? (bool) $data['sin_diagnostico'] : null,
            sucursalRegistroId: $sucursalRegistroId,
            sort: (string) ($data['sort'] ?? 'nombre_completo'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
            puedeBuscarTelefonoTutor: $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT),
        );

        $cantidad = $exporter->count($builder);
        $maxRows = (int) config('pacientes.export_max_rows', 10000);

        if ($cantidad > $maxRows) {
            throw new PacienteExportTooLargeException($maxRows);
        }

        $search = trim((string) ($data['search'] ?? ''));

        $audit->registrarExportacion(
            recurso: 'pacientes_listado',
            filtros: [
                'search_presente' => $search !== '',
                'search_longitud' => mb_strlen($search),
                'estado' => $data['estado'] ?? null,
                'tipo_diagnostico_id' => $data['tipo_diagnostico_id'] ?? null,
                'sin_diagnostico' => $data['sin_diagnostico'] ?? null,
                'sucursal_registro_id' => $sucursalRegistroId,
                'sort' => (string) ($data['sort'] ?? 'nombre_completo'),
                'direction' => (string) ($data['direction'] ?? 'asc'),
                'formato' => 'csv',
            ],
            cantidad: $cantidad,
            sucursalId: $sucursalRegistroId,
            usuarioId: $actor->id,
            ip: $request->ip(),
        );

        $filename = 'pacientes_'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($builder, $exporter, $actor): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            $exporter->write($builder, $actor, $handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function options(Request $request, GetPacienteFormOptionsQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($actor),
            'can' => PacienteAbilities::collection($actor),
        ]);
    }

    public function show(
        int $id,
        Request $request,
        GetPacienteQuery $query,
        AuditRecorderContract $audit,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $paciente = $query($id, $actor);

        $data = PacienteResource::forFicha($actor, $paciente, $request);

        // Solo la apertura de la ficha se audita (04.01/RF-013, 04.01/RN-009).
        $audit->registrarConsultaSensible(
            entidad: 'paciente',
            entidadId: (int) $paciente->id,
            sucursalId: (int) $paciente->sucursal_registro_id,
            contexto: ['recurso' => 'ficha_paciente'],
            usuarioId: $actor->id,
            ip: $request->ip(),
        );

        return response()->json([
            'data' => $data,
            'can' => PacienteAbilities::collection($actor),
        ]);
    }

    public function store(StorePacienteRequest $request, CreatePacienteAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $paciente = $action($request->validated(), $actor, $request->ip());
        $paciente->load(['sucursalRegistro', 'relacionesTutor.tutor']);

        return response()->json([
            'data' => PacienteResource::forFicha($actor, $paciente, $request),
            'can' => PacienteAbilities::collection($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdatePacienteRequest $request,
        GetPacienteQuery $getPaciente,
        UpdatePacienteAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $paciente = $getPaciente($id, $actor);
        $paciente = $action($paciente, $data, $expectedVersion, $actor, $request->ip());

        return response()->json([
            'data' => PacienteResource::forFicha($actor, $paciente, $request),
            'can' => PacienteAbilities::collection($actor),
        ]);
    }

    /**
     * Rechaza siempre la eliminación física (04.01/RF-009).
     */
    public function destroy(
        int $id,
        Request $request,
        GetPacienteQuery $getPaciente,
        RejectPacienteDeletionAction $action,
    ): never {
        /** @var User $actor */
        $actor = $request->user();

        $getPaciente($id, $actor);
        $action();
    }
}
