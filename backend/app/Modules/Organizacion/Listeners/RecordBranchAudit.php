<?php

namespace App\Modules\Organizacion\Listeners;

use App\Modules\Organizacion\Events\BranchActivated;
use App\Modules\Organizacion\Events\BranchCreated;
use App\Modules\Organizacion\Events\BranchDeactivated;
use App\Modules\Organizacion\Events\BranchUpdated;
use App\Modules\Organizacion\Models\BitacoraEntry;

/**
 * Registra en bitácora los eventos del módulo (RF-011 / RNF-013).
 *
 * No invoca Actions ni modifica la sucursal. La bitácora es solo inserción.
 */
final class RecordBranchAudit
{
    public function handle(BranchCreated|BranchUpdated|BranchActivated|BranchDeactivated $event): void
    {
        if ($event instanceof BranchCreated) {
            $this->record($event->branchId, $event->actorId, 'CREAR', null, $event->snapshot, null);

            return;
        }

        if ($event instanceof BranchUpdated) {
            $this->record($event->branchId, $event->actorId, 'ACTUALIZAR', $event->before, $event->after, null);

            return;
        }

        if ($event instanceof BranchActivated) {
            $this->record($event->branchId, $event->actorId, 'ACTIVAR', $event->before, $event->after, $event->reason);

            return;
        }

        $this->record($event->branchId, $event->actorId, 'DESACTIVAR', $event->before, $event->after, $event->reason);
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function record(
        int $branchId,
        ?int $actorId,
        string $action,
        ?array $before,
        ?array $after,
        ?string $reason,
    ): void {
        BitacoraEntry::query()->create([
            'usuario_id' => $actorId,
            'sucursal_id' => $branchId,
            'accion' => $action,
            'entidad' => 'sucursal',
            'entidad_id' => $branchId,
            'datos_anteriores' => $before,
            'datos_nuevos' => $after,
            'motivo' => $reason,
        ]);
    }
}
