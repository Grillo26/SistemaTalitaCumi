<?php

namespace App\Modules\Pacientes\Http\Controllers\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Actions\Tutores\CreateTutorAction;
use App\Modules\Pacientes\Actions\Tutores\UpdateTutorAction;
use App\Modules\Pacientes\Http\Requests\Tutores\ExportTutorRequest;
use App\Modules\Pacientes\Http\Requests\Tutores\IndexTutorRequest;
use App\Modules\Pacientes\Http\Requests\Tutores\StoreTutorRequest;
use App\Modules\Pacientes\Http\Requests\Tutores\UpdateTutorRequest;
use App\Modules\Pacientes\Http\Resources\TutorResource;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Queries\Tutores\GetTutorFormOptionsQuery;
use App\Modules\Pacientes\Queries\Tutores\GetTutorQuery;
use App\Modules\Pacientes\Queries\Tutores\ListTutoresQuery;
use App\Modules\Pacientes\Support\TutorAbilities;
use App\Modules\Pacientes\Support\TutorAccessScope;
use App\Modules\Pacientes\Support\TutorBranchResolver;
use App\Modules\Pacientes\Support\TutorCsvExporter;
use App\Modules\Seguridad\Contracts\AuditRecorderContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Registro de tutores — módulo 04.02.
 *
 * 04.02/RF-001, RF-002, RF-003, RF-007, RF-008, RF-009.
 */
final class TutorController
{
    public function __construct(private readonly TutorAccessScope $scope) {}

    public function index(IndexTutorRequest $request, ListTutoresQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginator = $query(
            search: $data['search'] ?? null,
            activo: array_key_exists('activo', $data) ? (bool) $data['activo'] : null,
            sucursalRegistroId: isset($data['sucursal_registro_id']) ? (int) $data['sucursal_registro_id'] : null,
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre_completo'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
        );

        $items = collect($paginator->items())
            ->map(static fn (Tutor $tutor): array => TutorResource::forList($actor, $tutor, $request))
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
            'can' => TutorAbilities::collection($actor),
        ]);
    }

    public function export(
        ExportTutorRequest $request,
        ListTutoresQuery $query,
        TutorCsvExporter $exporter,
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
            activo: array_key_exists('activo', $data) ? (bool) $data['activo'] : null,
            sucursalRegistroId: $sucursalRegistroId,
            sort: (string) ($data['sort'] ?? 'nombre_completo'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
        );

        $cantidad = $exporter->count($builder);

        $audit->registrarExportacion(
            recurso: 'tutores',
            filtros: array_merge($data, ['formato' => 'csv']),
            cantidad: $cantidad,
            sucursalId: $sucursalRegistroId,
            usuarioId: $actor->id,
            ip: $request->ip(),
        );

        $filename = 'tutores_'.now()->format('Ymd_His').'.csv';

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

    public function options(Request $request, GetTutorFormOptionsQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($actor),
            'can' => TutorAbilities::collection($actor),
        ]);
    }

    public function show(
        int $id,
        Request $request,
        GetTutorQuery $query,
        AuditRecorderContract $audit,
        TutorBranchResolver $branches,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $tutor = $query($id, $actor);

        $data = TutorResource::forFicha($actor, $tutor, $request);

        $audit->registrarConsultaSensible(
            entidad: 'tutor',
            entidadId: (int) $tutor->id,
            sucursalId: $branches->forConsultation($actor, $tutor),
            contexto: ['recurso' => 'ficha_tutor'],
            usuarioId: $actor->id,
            ip: $request->ip(),
        );

        return response()->json([
            'data' => $data,
            'can' => TutorAbilities::collection($actor),
        ]);
    }

    public function store(StoreTutorRequest $request, CreateTutorAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $sucursalId = $this->scope->assertCanCreate(
            $actor,
            isset($data['sucursal_registro_id']) ? (int) $data['sucursal_registro_id'] : null,
        );

        $tutor = $action($data, $actor, $request->ip(), $sucursalId);
        $tutor->load(['parentescoCatalogo', 'sucursalRegistro']);

        return response()->json([
            'data' => TutorResource::forFicha($actor, $tutor, $request),
            'can' => TutorAbilities::collection($actor),
        ], 201);
    }

    public function update(
        int $id,
        UpdateTutorRequest $request,
        GetTutorQuery $getTutor,
        UpdateTutorAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $tutor = $action($getTutor($id, $actor), $data, $expectedVersion, $actor, $request->ip());

        return response()->json([
            'data' => TutorResource::forFicha($actor, $tutor, $request),
            'can' => TutorAbilities::collection($actor),
        ]);
    }
}
