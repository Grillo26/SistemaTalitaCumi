<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Models\Feriado;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;

/**
 * [Programación de Turnos] RF-016 / RN-008.
 *
 * Lista feriados globales y de las sucursales accesibles al usuario.
 */
final class ListFeriados
{
    public function __construct(private readonly UserBranchAccessContract $branchAccess) {}

    /**
     * @param  array{sucursal_id?: int|null, anio?: int|null, activo?: bool|null}  $filtros
     * @return list<Feriado>
     */
    public function __invoke(User $actor, array $filtros = []): array
    {
        $accesibles = $this->branchAccess->accesibles((int) $actor->id);

        if (isset($filtros['sucursal_id'])) {
            $sucursalId = (int) $filtros['sucursal_id'];

            if (! in_array($sucursalId, $accesibles, true)) {
                throw new BranchAccessDeniedException;
            }
        }

        $query = Feriado::query()->orderBy('fecha');

        if (isset($filtros['sucursal_id'])) {
            $query->where(static function ($sub) use ($filtros): void {
                $sub->whereNull('sucursal_id')
                    ->orWhere('sucursal_id', (int) $filtros['sucursal_id']);
            });
        } else {
            $query->where(static function ($sub) use ($accesibles): void {
                $sub->whereNull('sucursal_id');

                if ($accesibles !== []) {
                    $sub->orWhereIn('sucursal_id', $accesibles);
                }
            });
        }

        if (isset($filtros['anio'])) {
            $anio = (int) $filtros['anio'];
            $query->whereBetween('fecha', ["{$anio}-01-01", "{$anio}-12-31"]);
        }

        if (array_key_exists('activo', $filtros) && $filtros['activo'] !== null) {
            $query->where('activo', (bool) $filtros['activo']);
        }

        return $query->get()->all();
    }
}
