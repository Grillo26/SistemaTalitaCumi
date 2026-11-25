<?php

namespace App\Modules\Pacientes\Http\Controllers\Consentimiento;

use App\Models\User;
use App\Modules\Pacientes\Actions\Consentimiento\CreateConsentimientoTipoAction;
use App\Modules\Pacientes\Actions\Consentimiento\RejectConsentimientoTipoDeletionAction;
use App\Modules\Pacientes\Actions\Consentimiento\UpdateConsentimientoTipoAction;
use App\Modules\Pacientes\Http\Requests\Consentimiento\StoreConsentimientoTipoRequest;
use App\Modules\Pacientes\Http\Requests\Consentimiento\UpdateConsentimientoTipoRequest;
use App\Modules\Pacientes\Http\Resources\ConsentimientoTipoResource;
use App\Modules\Pacientes\Queries\Consentimiento\GetConsentimientoTipoQuery;
use App\Modules\Pacientes\Queries\Consentimiento\ListConsentimientoTiposQuery;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Catálogo de tipos de consentimiento (04.03 Fase B).
 *
 * Solo Superadmin administra. No se elimina: se activa o desactiva.
 */
final class ConsentimientoTipoController
{
    public function index(
        Request $request,
        ListConsentimientoTiposQuery $getTipos,
    ): JsonResponse {
        $tipos = $getTipos($request->boolean('incluir_inactivos'));

        return response()->json([
            'data' => $tipos
                ->map(static fn ($tipo): ?array => ConsentimientoTipoResource::for($tipo))
                ->values()
                ->all(),
        ]);
    }

    public function store(
        StoreConsentimientoTipoRequest $request,
        CreateConsentimientoTipoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $tipo = $action($request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => ConsentimientoTipoResource::for($tipo),
        ], 201);
    }

    public function update(
        int $tipoId,
        UpdateConsentimientoTipoRequest $request,
        GetConsentimientoTipoQuery $getTipo,
        UpdateConsentimientoTipoAction $action,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();
        $tipo = $getTipo($tipoId);
        $data = $request->validated();
        $expectedVersion = (int) $data['version'];
        unset($data['version']);

        $tipo = $action($tipo, $data, $expectedVersion, $actor, $request->ip());

        return response()->json([
            'data' => ConsentimientoTipoResource::for($tipo),
        ]);
    }

    public function destroy(
        int $tipoId,
        Request $request,
        GetConsentimientoTipoQuery $getTipo,
        RejectConsentimientoTipoDeletionAction $action,
    ): never {
        $getTipo($tipoId);
        $action();
    }
}
