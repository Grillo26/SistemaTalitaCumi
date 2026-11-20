<?php

namespace App\Modules\Organizacion\Listeners;

use App\Modules\Organizacion\Events\AmbienteCreated;
use App\Modules\Organizacion\Events\AmbienteStatusChanged;
use App\Modules\Organizacion\Events\AmbienteUpdated;
use App\Modules\Organizacion\Events\SalaCreated;
use App\Modules\Organizacion\Events\SalaStatusChanged;
use App\Modules\Organizacion\Events\SalaUpdated;
use App\Modules\Organizacion\Events\TipoSalaCreated;
use App\Modules\Organizacion\Events\TipoSalaStatusChanged;
use App\Modules\Organizacion\Events\TipoSalaUpdated;
use App\Modules\Organizacion\Events\TurnoCatalogoCreated;
use App\Modules\Organizacion\Events\TurnoCatalogoStatusChanged;
use App\Modules\Organizacion\Events\TurnoCatalogoUpdated;
use App\Modules\Organizacion\Models\BitacoraEntry;

/**
 * Registra en bitácora los eventos de salas y ambientes (RF-015 / RNF-013).
 *
 * Entidades: tipo_sala, turno_catalogo, sala, ambiente.
 * Acciones: CREAR, ACTUALIZAR, ACTIVAR, DESACTIVAR.
 */
final class RecordSalasAmbientesAudit
{
    public function handle(
        TipoSalaCreated|TipoSalaUpdated|TipoSalaStatusChanged|
        TurnoCatalogoCreated|TurnoCatalogoUpdated|TurnoCatalogoStatusChanged|
        SalaCreated|SalaUpdated|SalaStatusChanged|
        AmbienteCreated|AmbienteUpdated|AmbienteStatusChanged $event,
    ): void {
        $payload = match (true) {
            $event instanceof TipoSalaCreated => [
                'entity' => 'tipo_sala',
                'id' => $event->tipoSalaId,
                'branch' => null,
                'action' => 'CREAR',
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof TipoSalaUpdated => [
                'entity' => 'tipo_sala',
                'id' => $event->tipoSalaId,
                'branch' => null,
                'action' => 'ACTUALIZAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof TipoSalaStatusChanged => [
                'entity' => 'tipo_sala',
                'id' => $event->tipoSalaId,
                'branch' => null,
                'action' => $event->activo ? 'ACTIVAR' : 'DESACTIVAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->reason,
                'actor' => $event->actorId,
            ],
            $event instanceof TurnoCatalogoCreated => [
                'entity' => 'turno_catalogo',
                'id' => $event->turnoCatalogoId,
                'branch' => null,
                'action' => 'CREAR',
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof TurnoCatalogoUpdated => [
                'entity' => 'turno_catalogo',
                'id' => $event->turnoCatalogoId,
                'branch' => null,
                'action' => 'ACTUALIZAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof TurnoCatalogoStatusChanged => [
                'entity' => 'turno_catalogo',
                'id' => $event->turnoCatalogoId,
                'branch' => null,
                'action' => $event->activo ? 'ACTIVAR' : 'DESACTIVAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->reason,
                'actor' => $event->actorId,
            ],
            $event instanceof SalaCreated => [
                'entity' => 'sala',
                'id' => $event->salaId,
                'branch' => $event->snapshot['sucursal_id'] ?? null,
                'action' => 'CREAR',
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof SalaUpdated => [
                'entity' => 'sala',
                'id' => $event->salaId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => 'ACTUALIZAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof SalaStatusChanged => [
                'entity' => 'sala',
                'id' => $event->salaId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => $event->activo ? 'ACTIVAR' : 'DESACTIVAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->reason,
                'actor' => $event->actorId,
            ],
            $event instanceof AmbienteCreated => [
                'entity' => 'ambiente',
                'id' => $event->ambienteId,
                'branch' => $event->snapshot['sucursal_id'] ?? null,
                'action' => 'CREAR',
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof AmbienteUpdated => [
                'entity' => 'ambiente',
                'id' => $event->ambienteId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => 'ACTUALIZAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
            ],
            $event instanceof AmbienteStatusChanged => [
                'entity' => 'ambiente',
                'id' => $event->ambienteId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => $event->activo ? 'ACTIVAR' : 'DESACTIVAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->reason,
                'actor' => $event->actorId,
            ],
        };

        BitacoraEntry::query()->create([
            'usuario_id' => $payload['actor'],
            'sucursal_id' => $payload['branch'],
            'accion' => $payload['action'],
            'entidad' => $payload['entity'],
            'entidad_id' => $payload['id'],
            'datos_anteriores' => $payload['before'],
            'datos_nuevos' => $payload['after'],
            'motivo' => $payload['reason'],
        ]);
    }
}
