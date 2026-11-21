<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\SalaNotFoundException;
use App\Modules\Organizacion\Models\Sala;

final class GetSalaQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(int $id, int $actorId, bool $hasGlobalScope): Sala
    {
        $sala = Sala::query()
            ->with(['sucursal', 'area', 'tipoSala', 'turnoCatalogo'])
            ->find($id);

        if ($sala === null) {
            throw new SalaNotFoundException($id);
        }

        if (! $hasGlobalScope && ! $this->access->puedeAcceder($actorId, $sala->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        return $sala;
    }
}
