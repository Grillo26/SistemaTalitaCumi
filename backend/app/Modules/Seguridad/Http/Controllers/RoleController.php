<?php

namespace App\Modules\Seguridad\Http\Controllers;

use App\Models\User;
use App\Modules\Seguridad\Actions\Roles\RejectRoleDeletionAction;
use App\Modules\Seguridad\Actions\Roles\UpdateRolePermissionsAction;
use App\Modules\Seguridad\Http\Requests\RoleMatrixHistoryRequest;
use App\Modules\Seguridad\Http\Requests\UpdateRolePermissionsRequest;
use App\Modules\Seguridad\Queries\Roles\GetRoleMatrixAtDateQuery;
use App\Modules\Seguridad\Queries\Roles\GetRoleMatrixQuery;
use App\Modules\Seguridad\Queries\Roles\ListRolesMatrixQuery;
use App\Modules\Seguridad\Support\SecurityPermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Roles y matriz de permisos — módulo 02, Fase 3.
 *
 * 02/RF-R01–R04, R08, R10, R13–R15.
 */
final class RoleController
{
    public function index(Request $request, ListRolesMatrixQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($actor),
            'can' => $this->abilities($actor),
        ]);
    }

    public function show(int $id, Request $request, GetRoleMatrixQuery $query): JsonResponse
    {
        /** @var User $actor */
        $actor = $request->user();

        return response()->json([
            'data' => $query($id, $actor),
            'can' => $this->abilities($actor),
        ]);
    }

    public function update(
        int $id,
        UpdateRolePermissionsRequest $request,
        UpdateRolePermissionsAction $action,
        GetRoleMatrixQuery $query,
    ): JsonResponse {
        /** @var User $actor */
        $actor = $request->user();

        $action($id, $request->validated(), $actor, $request->ip());

        return response()->json([
            'data' => $query($id, $actor),
            'can' => $this->abilities($actor),
        ]);
    }

    public function history(
        int $id,
        RoleMatrixHistoryRequest $request,
        GetRoleMatrixAtDateQuery $query,
    ): JsonResponse {
        $data = $request->validated();

        return response()->json([
            'data' => $query($id, (string) $data['fecha']),
        ]);
    }

    /**
     * Rechaza siempre la eliminación de roles (02/RN-17).
     */
    public function destroy(int $id, Request $request, RejectRoleDeletionAction $action): never
    {
        /** @var User $actor */
        $actor = $request->user();

        $action($id, $actor);
    }

    /**
     * @return array<string, bool>
     */
    private function abilities(User $actor): array
    {
        return [
            'edit' => $actor->can(SecurityPermission::ROLE_EDIT_PERMISSIONS),
            'export' => false,
        ];
    }
}
