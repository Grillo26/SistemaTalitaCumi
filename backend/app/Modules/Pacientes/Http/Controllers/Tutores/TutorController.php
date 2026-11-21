<?php

namespace App\Modules\Pacientes\Http\Controllers\Tutores;

use App\Models\User;
use App\Modules\Pacientes\Actions\Tutores\CreateTutorAction;
use App\Modules\Pacientes\Actions\Tutores\UpdateTutorAction;
use App\Modules\Pacientes\Http\Requests\Tutores\IndexTutorRequest;
use App\Modules\Pacientes\Http\Requests\Tutores\StoreTutorRequest;
use App\Modules\Pacientes\Http\Requests\Tutores\UpdateTutorRequest;
use App\Modules\Pacientes\Http\Resources\TutorResource;
use App\Modules\Pacientes\Models\Tutor;
use App\Modules\Pacientes\Queries\Tutores\GetTutorQuery;
use App\Modules\Pacientes\Queries\Tutores\ListTutoresQuery;
use App\Modules\Pacientes\Support\TutorAbilities;
use App\Modules\Pacientes\Support\TutorAccessScope;
use Illuminate\Http\JsonResponse;

/**
 * Registro de tutores — módulo 04.02 (núcleo).
 *
 * 04.02/RF-001, RF-003, RF-007, RF-008, RF-009.
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
            page: (int) ($data['page'] ?? 1),
            perPage: (int) ($data['per_page'] ?? 10),
            sort: (string) ($data['sort'] ?? 'nombre_completo'),
            direction: (string) ($data['direction'] ?? 'asc'),
            actor: $actor,
        );

        $items = collect($paginator->items())
            ->map(static fn (Tutor $tutor): array => TutorResource::for($actor, $tutor, $request))
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

    public function show(int $id, GetTutorQuery $query, \Illuminate\Http\Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => TutorResource::for($actor, $query($id, $actor), $request),
            'can' => TutorAbilities::collection($actor),
        ]);
    }

    public function store(StoreTutorRequest $request, CreateTutorAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $this->scope->assertCanCreate($actor);

        $tutor = $action($request->validated(), $actor->id, $request->ip());

        return response()->json([
            'data' => TutorResource::for($actor, $tutor, $request),
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
            'data' => TutorResource::for($actor, $tutor, $request),
            'can' => TutorAbilities::collection($actor),
        ]);
    }
}
