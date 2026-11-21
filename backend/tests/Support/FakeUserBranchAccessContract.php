<?php

namespace Tests\Support;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;

/**
 * Doble de prueba para el contrato de alcance por sucursal.
 *
 * Se usa solo en tests para simular distintas asignaciones. La
 * implementación de producción es EloquentUserBranchAccess.
 */
final class FakeUserBranchAccessContract implements UserBranchAccessContract
{
    /**
     * @var array<int, list<int>>
     */
    public array $accessibleByUser = [];

    public function accesibles(int $usuarioId): array
    {
        return $this->accessibleByUser[$usuarioId] ?? [];
    }

    public function puedeAcceder(int $usuarioId, int $sucursalId): bool
    {
        return in_array($sucursalId, $this->accesibles($usuarioId), true);
    }
}
