<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\SinSucursalesAsignadasException;
use App\Modules\AgendaTurnos\Exceptions\SucursalesNoDisponiblesException;
use App\Modules\AgendaTurnos\Support\AgendaTurnosPermission;
use App\Modules\Organizacion\Contracts\BranchDirectoryContract;
use App\Modules\Organizacion\Contracts\BranchResumen;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Seguridad\Contracts\PermissionEvaluatorContract;
use Throwable;

/**
 * [Agenda Semanal] RF-011, RN-001, RN-004, CA-007, CU-01 FA-01.
 *
 * Solo devuelve las sucursales asignadas al usuario y activas. La primera
 * ordenada por nombre es la predeterminada mientras no exista un campo
 * "sucursal principal" en el schema.
 */
final class GetSucursalesDisponibles
{
    public function __construct(
        private readonly UserBranchAccessContract $access,
        private readonly BranchDirectoryContract $branches,
        private readonly PermissionEvaluatorContract $permissions,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function __invoke(User $actor): array
    {
        $ids = $this->access->accesibles($actor->id);

        if ($ids === []) {
            throw new SinSucursalesAsignadasException;
        }

        try {
            $resumenes = $this->branches->resumenes($ids);
        } catch (Throwable) {
            throw new SucursalesNoDisponiblesException;
        }

        if ($resumenes === []) {
            throw new SucursalesNoDisponiblesException;
        }

        usort(
            $resumenes,
            static fn (BranchResumen $a, BranchResumen $b): int => mb_strtolower($a->nombre) <=> mb_strtolower($b->nombre),
        );

        return [
            'data' => array_map(
                static fn (BranchResumen $branch): array => $branch->toArray(),
                $resumenes,
            ),
            'predeterminada_id' => $resumenes[0]->id,
            'can' => [
                'ver' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::VER,
                ),
                'exportar' => $this->permissions->puede(
                    $actor->id,
                    AgendaTurnosPermission::EXPORTAR,
                ),
            ],
            'zona_horaria' => (string) config('app.timezone', 'America/La_Paz'),
        ];
    }
}
