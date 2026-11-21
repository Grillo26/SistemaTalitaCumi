<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\DeletionNotAllowedException;
use App\Modules\Organizacion\Exceptions\SalaNotFoundException;
use App\Modules\Organizacion\Models\Sala;

/**
 * Rechazo explícito de eliminación de sala (RF-012 / RN-007 / CA-007).
 */
final class RejectSalaDeletionAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(int $id, ?int $actorId, bool $hasGlobalScope): never
    {
        $sala = Sala::query()->find($id);

        if ($sala === null) {
            throw new SalaNotFoundException($id);
        }

        if (! $hasGlobalScope
            && ($actorId === null || ! $this->access->puedeAcceder($actorId, $sala->sucursal_id))) {
            throw new BranchAccessDeniedException;
        }

        throw new DeletionNotAllowedException('la sala', $sala->nombre);
    }
}
