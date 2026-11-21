<?php

namespace App\Modules\Organizacion\Contracts;

/**
 * Acceso del usuario autenticado a las sucursales (02/RF-U16 / 02/RN-03).
 *
 * Implementación real: App\Modules\Seguridad\Contracts\EloquentUserBranchAccess,
 * bindeada en SeguridadServiceProvider. Fail-closed: sin sucursales
 * asignadas devuelve una lista vacía; nunca “todas” como atajo.
 */
interface UserBranchAccessContract
{
    /**
     * @return list<int>
     */
    public function accesibles(int $usuarioId): array;

    public function puedeAcceder(int $usuarioId, int $sucursalId): bool;
}
