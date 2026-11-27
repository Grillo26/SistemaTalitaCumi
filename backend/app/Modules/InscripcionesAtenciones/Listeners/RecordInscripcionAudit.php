<?php

namespace App\Modules\InscripcionesAtenciones\Listeners;

use App\Modules\InscripcionesAtenciones\Events\InscripcionCreated;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRenewed;
use App\Modules\InscripcionesAtenciones\Events\InscripcionRetirada;
use App\Modules\InscripcionesAtenciones\Events\InscripcionUpdated;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionAuditUnavailableException;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAuditAction;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use Throwable;

/**
 * Bitácora de inscripciones (RNF-012).
 *
 * Síncrona y fail-closed: si no se puede auditar, la transacción se revierte.
 */
final class RecordInscripcionAudit
{
    public function handle(
        InscripcionCreated|InscripcionUpdated|InscripcionRenewed|InscripcionRetirada $event,
    ): void {
        $payload = match (true) {
            $event instanceof InscripcionCreated => [
                'entity_id' => $event->inscripcionId,
                'branch' => $event->sucursalId,
                'action' => InscripcionAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof InscripcionUpdated => [
                'entity_id' => $event->inscripcionId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => InscripcionAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof InscripcionRenewed => [
                'entity_id' => $event->inscripcionId,
                'branch' => $event->sucursalId,
                'action' => InscripcionAuditAction::RENEW,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => 'Renovación de inscripción #'.$event->inscripcionOrigenId,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof InscripcionRetirada => [
                'entity_id' => $event->inscripcionId,
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
                'entidad' => InscripcionesAtencionesModule::ENTITY_INSCRIPCION_SALA,
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
            throw new InscripcionAuditUnavailableException($exception);
        }
    }
}
