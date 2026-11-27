<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\SinSucursalesAsignadasException;
use App\Modules\AgendaTurnos\Exceptions\SucursalesNoDisponiblesException;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\BranchResumen;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use Throwable;

/**
 * [Agenda Semanal] RF-011, RN-001, RN-004, CA-007.
 *
 * Scope explícito por sucursal, sin GlobalScope. Reutiliza el mismo contrato
 * que Salas/Inscripciones y falla cerrado si el usuario no tiene sucursales.
 */
final class AgendaAccessScope
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
    ) {}

    /**
     * @return list<int>
     */
    public function accesibles(User $actor): array
    {
        $ids = $this->access->accesibles($actor->id);

        if ($ids === []) {
            throw new SinSucursalesAsignadasException;
        }

        return $ids;
    }

    public function resolverSucursal(User $actor, ?int $solicitada): int
    {
        $accesibles = $this->accesibles($actor);

        if ($solicitada !== null) {
            if (! in_array($solicitada, $accesibles, true)) {
                throw new BranchAccessDeniedException;
            }

            return $solicitada;
        }

        try {
            $resumenes = $this->branches->resumenes($accesibles);
        } catch (Throwable $exception) {
            throw new SucursalesNoDisponiblesException;
        }

        if ($resumenes === []) {
            throw new SucursalesNoDisponiblesException;
        }

        usort(
            $resumenes,
            static fn (BranchResumen $a, BranchResumen $b): int => mb_strtolower($a->nombre) <=> mb_strtolower($b->nombre),
        );

        return $resumenes[0]->id;
    }

    public function assertSucursal(User $actor, int $sucursalId): void
    {
        if (! in_array($sucursalId, $this->accesibles($actor), true)) {
            throw new BranchAccessDeniedException;
        }
    }

    public function resumenSucursal(int $sucursalId): BranchResumen
    {
        try {
            $resumenes = $this->branches->resumenes([$sucursalId]);
        } catch (Throwable) {
            throw new SucursalesNoDisponiblesException;
        }

        if (! isset($resumenes[$sucursalId])) {
            throw new SucursalesNoDisponiblesException;
        }

        return $resumenes[$sucursalId];
    }
}
