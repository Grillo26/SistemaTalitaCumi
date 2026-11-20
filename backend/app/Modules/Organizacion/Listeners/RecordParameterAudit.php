<?php

namespace App\Modules\Organizacion\Listeners;

use App\Modules\Organizacion\Events\ParameterCreated;
use App\Modules\Organizacion\Events\ParameterRestored;
use App\Modules\Organizacion\Events\ParameterUpdated;
use App\Modules\Organizacion\Models\BitacoraEntry;

/**
 * Registra en bitácora los cambios de parámetros (RF-013 / RN-007 / RN-008).
 *
 * Entidad: `parametro`. Sin sucursal (RN-009). Acciones: CREAR, EDITAR y
 * RESTAURAR. El alta inicial la emite el seeder del catálogo.
 */
final class RecordParameterAudit
{
    public function handle(ParameterCreated|ParameterUpdated|ParameterRestored $event): void
    {
        if ($event instanceof ParameterCreated) {
            $this->record(
                parametroId: $event->parametroId,
                actorId: $event->actorId,
                action: 'CREAR',
                before: null,
                after: $event->snapshot,
                reason: null,
            );

            return;
        }

        if ($event instanceof ParameterUpdated) {
            $this->record(
                parametroId: $event->parametroId,
                actorId: $event->actorId,
                action: 'EDITAR',
                before: $event->before,
                after: $event->after,
                reason: $event->motivo,
            );

            return;
        }

        $this->record(
            parametroId: $event->parametroId,
            actorId: $event->actorId,
            action: 'RESTAURAR',
            before: $event->before,
            after: $event->after,
            reason: $event->motivo,
        );
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    private function record(
        int $parametroId,
        ?int $actorId,
        string $action,
        ?array $before,
        ?array $after,
        ?string $reason,
    ): void {
        BitacoraEntry::query()->create([
            'usuario_id' => $actorId,
            'sucursal_id' => null,
            'accion' => $action,
            'entidad' => 'parametro',
            'entidad_id' => $parametroId,
            'datos_anteriores' => $before,
            'datos_nuevos' => $after,
            'motivo' => $reason,
        ]);
    }
}
