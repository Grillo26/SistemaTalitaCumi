<?php

namespace App\Modules\Seguridad\Contracts;

/**
 * Evaluador de permisos para módulos consumidores (02/RF-R05, 02/RF-R11).
 *
 * Fail-closed: si el usuario no existe, está inactivo o el servicio falla,
 * devuelve `false` / lista vacía. La UI nunca es la única barrera.
 */
interface PermissionEvaluatorContract
{
    public function puede(int $usuarioId, string $permiso, ?int $sucursalId = null): bool;

    /**
     * @return list<string>
     */
    public function permisos(int $usuarioId): array;
}
