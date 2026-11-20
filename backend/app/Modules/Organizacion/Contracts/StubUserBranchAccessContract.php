<?php

namespace App\Modules\Organizacion\Contracts;

use App\Modules\Organizacion\Exceptions\ModulePendingException;

/**
 * Stub del módulo 02.
 *
 * TODO módulo 02: reemplazar por una implementación que consulte
 * `usuario_sucursal` (o su equivalente) y el usuario autenticado.
 */
final class StubUserBranchAccessContract implements UserBranchAccessContract
{
    public function accesibles(int $usuarioId): array
    {
        throw new ModulePendingException('Módulo 02 Usuarios y roles pendiente');
    }

    public function puedeAcceder(int $usuarioId, int $sucursalId): bool
    {
        throw new ModulePendingException('Módulo 02 Usuarios y roles pendiente');
    }
}
