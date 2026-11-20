<?php

namespace App\Modules\Organizacion\Queries;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\AmbienteNotFoundException;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Models\Ambiente;

final class GetAmbienteQuery
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(int $id, int $actorId, bool $hasGlobalScope): Ambiente
    {
        $ambiente = Ambiente::query()
            ->with('sucursal')
            ->find($id);

        if ($ambiente === null) {
            throw new AmbienteNotFoundException($id);
        }

        if (! $hasGlobalScope && ! $this->access->puedeAcceder($actorId, $ambiente->sucursal_id)) {
            throw new BranchAccessDeniedException;
        }

        return $ambiente;
    }
}
