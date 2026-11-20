<?php

namespace App\Modules\Organizacion\Http\Controllers;

use App\Models\User;
use App\Modules\Organizacion\Actions\ActivateBranchAction;
use App\Modules\Organizacion\Actions\DeactivateBranchAction;
use App\Modules\Organizacion\Http\Requests\ChangeBranchStatusRequest;
use App\Modules\Organizacion\Http\Resources\BranchResource;
use App\Modules\Organizacion\Support\BranchAbilities;
use App\Modules\Organizacion\Support\BranchPermission;
use Illuminate\Http\JsonResponse;

/**
 * Activación y desactivación de sucursales.
 *
 * RF-007 / RN-005: el motivo es obligatorio en ambos sentidos.
 */
final class BranchStatusController
{
    public function activate(int $id, ChangeBranchStatusRequest $request, ActivateBranchAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $branch = $action(
            branchId: $id,
            reason: (string) $data['motivo'],
            expectedVersion: (int) $data['version'],
            actorId: $user->id,
            hasGlobalScope: $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new BranchResource($branch))->resolve($request),
            'can' => BranchAbilities::for($user),
        ]);
    }

    public function deactivate(int $id, ChangeBranchStatusRequest $request, DeactivateBranchAction $action): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $data = $request->validated();

        $branch = $action(
            branchId: $id,
            reason: (string) $data['motivo'],
            expectedVersion: (int) $data['version'],
            actorId: $user->id,
            hasGlobalScope: $user->can(BranchPermission::GLOBAL_SCOPE),
        );

        return response()->json([
            'data' => (new BranchResource($branch))->resolve($request),
            'can' => BranchAbilities::for($user),
        ]);
    }
}
