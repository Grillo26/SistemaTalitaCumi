<?php

namespace App\Modules\Seguridad\Contracts;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Implementación Spatie + alcance por sucursal (02/RF-R05, 02/RF-R11).
 */
final class EloquentPermissionEvaluator implements PermissionEvaluatorContract
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function puede(int $usuarioId, string $permiso, ?int $sucursalId = null): bool
    {
        try {
            $user = User::query()->find($usuarioId);

            if ($user === null || ! $user->activo) {
                return false;
            }

            if (! $user->can($permiso)) {
                return false;
            }

            if ($sucursalId !== null && ! $this->access->puedeAcceder($usuarioId, $sucursalId)) {
                return false;
            }

            return true;
        } catch (Throwable $exception) {
            Log::warning('No se pudo evaluar el permiso; se aplica fail-closed.', [
                'usuario_id' => $usuarioId,
                'permiso' => $permiso,
                'sucursal_id' => $sucursalId,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @return list<string>
     */
    public function permisos(int $usuarioId): array
    {
        try {
            $user = User::query()->find($usuarioId);

            if ($user === null || ! $user->activo) {
                return [];
            }

            return $user->getAllPermissions()
                ->pluck('name')
                ->unique()
                ->sort()
                ->values()
                ->all();
        } catch (Throwable $exception) {
            Log::warning('No se pudieron listar los permisos; se aplica fail-closed.', [
                'usuario_id' => $usuarioId,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }
}
