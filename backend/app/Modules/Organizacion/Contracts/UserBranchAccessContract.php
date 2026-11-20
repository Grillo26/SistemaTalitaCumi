<?php

namespace App\Modules\Organizacion\Contracts;

/**
 * Acceso del usuario autenticado a las sucursales (RF-010 / RN-006).
 *
 * Implementación real: módulo 02 — Usuarios, roles y seguridad.
 * Mientras no exista, se usa StubUserBranchAccessContract, que falla de
 * forma explícita y nunca devuelve datos falsos.
 */
interface UserBranchAccessContract
{
    /**
     * @return list<int>
     */
    public function accesibles(int $usuarioId): array;

    public function puedeAcceder(int $usuarioId, int $sucursalId): bool;
}
