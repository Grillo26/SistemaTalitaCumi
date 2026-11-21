<?php

namespace App\Modules\Seguridad\Contracts;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Models\Branch;
use App\Modules\Organizacion\Support\BranchPermission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Implementación real del alcance por sucursal (02/RF-U16 / 02/RN-03).
 *
 * Fail-closed: usuario inexistente, inactivo o error de lectura devuelve
 * una lista vacía, nunca “todas las sucursales”.
 */
final class EloquentUserBranchAccess implements UserBranchAccessContract
{
    /**
     * @return list<int>
     */
    public function accesibles(int $usuarioId): array
    {
        try {
            $user = User::query()->find($usuarioId);

            if ($user === null || ! $user->activo) {
                return [];
            }

            if ($user->can(BranchPermission::GLOBAL_SCOPE)) {
                return Branch::query()
                    ->where('activo', true)
                    ->orderBy('id')
                    ->pluck('id')
                    ->map(static fn (mixed $id): int => (int) $id)
                    ->all();
            }

            return DB::table('usuario_sucursal')
                ->join('sucursal', 'sucursal.id', '=', 'usuario_sucursal.sucursal_id')
                ->where('usuario_sucursal.usuario_id', $usuarioId)
                ->where('sucursal.activo', true)
                ->orderBy('usuario_sucursal.sucursal_id')
                ->pluck('usuario_sucursal.sucursal_id')
                ->map(static fn (mixed $id): int => (int) $id)
                ->all();
        } catch (Throwable $exception) {
            Log::warning('No se pudo resolver el alcance por sucursal; se aplica fail-closed.', [
                'usuario_id' => $usuarioId,
                'error' => $exception->getMessage(),
            ]);

            return [];
        }
    }

    public function puedeAcceder(int $usuarioId, int $sucursalId): bool
    {
        return in_array($sucursalId, $this->accesibles($usuarioId), true);
    }
}
