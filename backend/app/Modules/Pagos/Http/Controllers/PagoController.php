<?php

namespace App\Modules\Pagos\Http\Controllers;

use App\Models\User;
use App\Modules\Pagos\Actions\RegistrarPagoAction;
use App\Modules\Pagos\Actions\RejectPagoDeletionAction;
use App\Modules\Pagos\Http\Requests\IndexPagoRequest;
use App\Modules\Pagos\Http\Requests\StorePagoRequest;
use App\Modules\Pagos\Queries\GetPagoQuery;
use App\Modules\Pagos\Queries\ListPagosQuery;
use App\Modules\Pagos\Support\PagoAbilities;
use App\Modules\Pagos\Support\PagoPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * [08.01] Endpoints de pagos. Delgado: Request -> Action | Query -> JSON.
 */
final class PagoController
{
    public function index(IndexPagoRequest $request, ListPagosQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        $data = $request->validated();

        $paginador = $query(
            $data,
            $actor,
            (int) ($data['page'] ?? 1),
            (int) ($data['per_page'] ?? 20),
            (string) ($data['sort'] ?? 'fecha_pago'),
            (string) ($data['direction'] ?? 'desc'),
        );

        return response()->json([
            'data' => collect($paginador->items())
                ->map(static fn ($pago): array => PagoPresenter::fila($pago))
                ->values(),
            'links' => [
                'first' => $paginador->url(1),
                'last' => $paginador->url($paginador->lastPage()),
                'prev' => $paginador->previousPageUrl(),
                'next' => $paginador->nextPageUrl(),
            ],
            'meta' => [
                'current_page' => $paginador->currentPage(),
                'last_page' => $paginador->lastPage(),
                'per_page' => $paginador->perPage(),
                'total' => $paginador->total(),
                'from' => $paginador->firstItem(),
                'to' => $paginador->lastItem(),
            ],
            'can' => PagoAbilities::for($actor),
        ]);
    }

    public function show(int $id, Request $request, GetPagoQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();
        ['pago' => $pago, 'grupo' => $grupo] = $query($id, $actor);

        return response()->json([
            'data' => PagoPresenter::fila($pago) + [
                'lineas' => $grupo->map(static fn ($linea): array => PagoPresenter::fila($linea))->values(),
            ],
            'can' => PagoAbilities::for($actor),
        ]);
    }

    public function store(StorePagoRequest $request, RegistrarPagoAction $action): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        $resultado = $action($request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => [
                'grupo_pago_id' => $resultado->grupoPagoId,
                'replay' => $resultado->replay,
                'saldo_resultante' => $resultado->saldoResultante,
                'lineas' => $resultado->pagos
                    ->map(static fn ($pago): array => PagoPresenter::fila($pago))
                    ->values(),
            ],
            'can' => PagoAbilities::for($actor),
        ], $resultado->replay ? 200 : 201);
    }

    public function destroy(int $id, RejectPagoDeletionAction $action): never
    {
        $action($id);
    }
}
