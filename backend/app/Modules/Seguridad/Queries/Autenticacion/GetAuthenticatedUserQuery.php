<?php

namespace App\Modules\Seguridad\Queries\Autenticacion;

use App\Models\User;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Support\BranchPermission;
use App\Modules\Seguridad\Support\SecurityParameters;

/**
 * Datos de la sesión actual (02/RF-A16, 02/RF-R05, 02/RF-R06).
 *
 * Devuelve usuario, rol, permisos efectivos, sucursales, política vigente
 * y marcas para que la UI oculte acciones. El backend siempre revalida.
 */
final class GetAuthenticatedUserQuery
{
    public function __construct(private readonly SecurityParameters $security) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $user): array
    {
        $hasGlobalScope = $user->can(BranchPermission::GLOBAL_SCOPE);

        $branches = $hasGlobalScope
            ? Branch::query()
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'codigo', 'nombre'])
            : $user->branches()
                ->where('sucursal.activo', true)
                ->orderBy('sucursal.nombre')
                ->get(['sucursal.id', 'sucursal.codigo', 'sucursal.nombre']);

        $policy = $this->security->passwordPolicy();
        $role = $user->getRoleNames()->first();

        return [
            'id' => $user->id,
            'nombre_completo' => $user->name,
            'username' => $user->username,
            'documento' => $user->documento,
            'email' => $user->email,
            'activo' => (bool) $user->activo,
            'rol' => $role,
            'permisos' => $user->getAllPermissions()
                ->pluck('name')
                ->unique()
                ->sort()
                ->values()
                ->all(),
            'sucursales' => $branches->map(static fn (Branch $branch): array => [
                'id' => $branch->id,
                'codigo' => $branch->codigo,
                'nombre' => $branch->nombre,
            ])->values()->all(),
            'alcance_global' => $hasGlobalScope,
            'requires_branch_assignment' => ! $hasGlobalScope && $branches->isEmpty(),
            'debe_cambiar_password' => (bool) $user->debe_cambiar_password,
            'ultimo_acceso' => $user->ultimo_acceso?->toIso8601String(),
            'password_expires_at' => $this->passwordExpiresAt($user, $policy->expiryDays),
            'password_policy' => $policy->toArray(),
        ];
    }

    private function passwordExpiresAt(User $user, int $expiryDays): ?string
    {
        if ($expiryDays <= 0 || $user->password_actualizada_en === null) {
            return null;
        }

        return $user->password_actualizada_en->copy()->addDays($expiryDays)->toIso8601String();
    }
}
