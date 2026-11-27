<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\TurnoNotFoundException;
use App\Modules\AgendaTurnos\Models\Turno;

/**
 * 07.04/RF-012, 07.04/RN-007, 07.04/CA-010.
 *
 * Alcance fail-closed. Un turno de una sucursal no asignada responde 404
 * para no revelar su existencia; los listados filtran por sucursales.
 */
final class AsistenciaAcceso
{
    public function __construct(private readonly AgendaAccessScope $scope) {}

    public function turno(int $id, User $actor): Turno
    {
        $turno = Turno::query()->find($id);

        if ($turno === null) {
            throw new TurnoNotFoundException($id);
        }

        $this->assertSucursal($actor, (int) $turno->sucursal_id, $id);

        return $turno;
    }

    public function turnoBloqueado(int $id, User $actor): Turno
    {
        $turno = Turno::query()->whereKey($id)->lockForUpdate()->first();

        if ($turno === null) {
            throw new TurnoNotFoundException($id);
        }

        $this->assertSucursal($actor, (int) $turno->sucursal_id, $id);

        return $turno;
    }

    /**
     * @return list<int>
     */
    public function sucursales(User $actor): array
    {
        return $this->scope->accesibles($actor);
    }

    private function assertSucursal(User $actor, int $sucursalId, int $turnoId): void
    {
        if (! in_array($sucursalId, $this->scope->accesibles($actor), true)) {
            throw new TurnoNotFoundException($turnoId);
        }
    }
}
