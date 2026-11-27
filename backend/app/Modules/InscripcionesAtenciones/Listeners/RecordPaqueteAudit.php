<?php

namespace App\Modules\InscripcionesAtenciones\Listeners;

use App\Modules\InscripcionesAtenciones\Events\PaqueteCreated;
use App\Modules\InscripcionesAtenciones\Events\PaqueteRenewed;
use App\Modules\InscripcionesAtenciones\Events\PaqueteRetirado;
use App\Modules\InscripcionesAtenciones\Events\PaqueteUpdated;
use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteAuditUnavailableException;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAuditAction;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use Throwable;

/**
 * Bitácora de paquetes de sesiones (RNF-012).
 *
 * Síncrona y fail-closed. No modifica el listener de Salas Grupales.
 */
final class RecordPaqueteAudit
{
    public function handle(
        PaqueteCreated|PaqueteUpdated|PaqueteRenewed|PaqueteRetirado $event,
    ): void {
        $payload = match (true) {
            $event instanceof PaqueteCreated => [
                'entity_id' => $event->paqueteId,
                'branch' => $event->sucursalId,
                'action' => InscripcionAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof PaqueteUpdated => [
                'entity_id' => $event->paqueteId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => InscripcionAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof PaqueteRenewed => [
                'entity_id' => $event->paqueteId,
                'branch' => $event->sucursalId,
                'action' => InscripcionAuditAction::RENEW,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => 'Renovación de paquete #'.$event->paqueteOrigenId,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof PaqueteRetirado => [
                'entity_id' => $event->paqueteId,
                'branch' => $event->sucursalId,
                'action' => InscripcionAuditAction::WITHDRAW,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'motivo_codigo' => $event->motivo->value,
                    'motivo_detalle' => $event->motivoDetalle,
                    'fecha_baja' => $event->fechaBaja,
                ]),
                'reason' => $event->motivo->label()
                    .($event->motivoDetalle !== null && $event->motivoDetalle !== ''
                        ? ': '.$event->motivoDetalle
                        : ''),
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => true,
            ],
        };

        try {
            BitacoraEntry::query()->create([
                'usuario_id' => $payload['actor'],
                'sucursal_id' => $payload['branch'],
                'accion' => $payload['action'],
                'entidad' => InscripcionesAtencionesModule::ENTITY_PAQUETE_SESIONES,
                'entidad_id' => $payload['entity_id'],
                'datos_anteriores' => $payload['before'],
                'datos_nuevos' => $payload['after'],
                'motivo' => $payload['reason'],
                'ip' => $payload['ip'],
                'modulo' => InscripcionesAtencionesModule::AUDIT_MODULE,
                'resultado' => 'exito',
                'critico' => $payload['critical'],
            ]);
        } catch (Throwable $exception) {
            throw new PaqueteAuditUnavailableException($exception);
        }
    }
}
