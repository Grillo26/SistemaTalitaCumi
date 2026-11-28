<?php

namespace App\Modules\Pagos\Queries;

use App\Models\User;
use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Pagos\Contracts\PayableEntityContract;

/**
 * Búsqueda de entidades a pagar por paciente, CI o número (08.01/RF-006).
 *
 * Solo sucursales accesibles al usuario; sin acceso, lista vacía.
 */
final class BuscarEntidadPagableQuery
{
    public function __construct(
        private readonly PayableEntityContract $entidades,
        private readonly UserBranchAccessContract $access,
    ) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function __invoke(string $termino, User $actor): array
    {
        $accesibles = $this->access->accesibles($actor->id);

        if ($accesibles === []) {
            return [];
        }

        return array_map(
            static fn ($entidad): array => $entidad->toArray(),
            $this->entidades->buscar(trim($termino), $accesibles),
        );
    }
}
