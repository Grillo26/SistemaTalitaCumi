<?php

namespace App\Modules\Organizacion\Actions;

use App\Modules\Organizacion\Contracts\UserBranchAccessContract;
use App\Modules\Organizacion\Exceptions\AmbienteNotFoundException;
use App\Modules\Organizacion\Exceptions\BranchAccessDeniedException;
use App\Modules\Organizacion\Exceptions\DeletionNotAllowedException;
use App\Modules\Organizacion\Models\Ambiente;

/**
 * Rechazo explícito de eliminación de ambiente (RF-012 / RN-007).
 */
final class RejectAmbienteDeletionAction
{
    public function __construct(private readonly UserBranchAccessContract $access) {}

    public function __invoke(int $id, ?int $actorId, bool $hasGlobalScope): never
    {
        $ambiente = Ambiente::query()->find($id);

        if ($ambiente === null) {
            throw new AmbienteNotFoundException($id);
        }

        if (! $hasGlobalScope
            && ($actorId === null || ! $this->access->puedeAcceder($actorId, $ambiente->sucursal_id))) {
            throw new BranchAccessDeniedException;
        }

        throw new DeletionNotAllowedException('el ambiente', $ambiente->nombre);
    }
}
