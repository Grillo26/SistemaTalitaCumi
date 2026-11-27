<?php

namespace App\Modules\InscripcionesAtenciones\Listeners;

use App\Modules\InscripcionesAtenciones\Events\AtencionRetirada;
use App\Modules\InscripcionesAtenciones\Events\AtencionUpdated;
use App\Modules\InscripcionesAtenciones\Events\DiagnosticoCreated;
use App\Modules\InscripcionesAtenciones\Events\EntrevistaCreated;
use App\Modules\InscripcionesAtenciones\Events\EntrevistaVinculada;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionAuditUnavailableException;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Support\AtencionAuditAction;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use Throwable;

/**
 * Bitácora de entrevistas y diagnósticos (RNF-012).
 *
 * Síncrona y fail-closed: si no se puede auditar, la transacción se revierte.
 */
final class RecordAtencionAudit
{
    public function handle(
        EntrevistaCreated|DiagnosticoCreated|AtencionUpdated|AtencionRetirada|EntrevistaVinculada $event,
    ): void {
        $payload = match (true) {
            $event instanceof EntrevistaCreated => [
                'entity' => InscripcionesAtencionesModule::ENTITY_ATENCION_ENTREVISTA,
                'entity_id' => $event->entrevistaId,
                'branch' => $event->sucursalId,
                'action' => AtencionAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof DiagnosticoCreated => [
                'entity' => InscripcionesAtencionesModule::ENTITY_ATENCION_DIAGNOSTICO,
                'entity_id' => $event->diagnosticoId,
                'branch' => $event->sucursalId,
                'action' => AtencionAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof AtencionUpdated => [
                'entity' => self::entityFor($event->tipo->value),
                'entity_id' => $event->atencionId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => AtencionAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof AtencionRetirada => [
                'entity' => self::entityFor($event->tipo->value),
                'entity_id' => $event->atencionId,
                'branch' => $event->sucursalId,
                'action' => AtencionAuditAction::WITHDRAW,
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
            $event instanceof EntrevistaVinculada => [
                'entity' => InscripcionesAtencionesModule::ENTITY_ATENCION_DIAGNOSTICO,
                'entity_id' => $event->diagnosticoId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => AtencionAuditAction::LINK,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'entrevista_vinculada_id' => $event->entrevistaId,
                ]),
                'reason' => 'Vinculación con entrevista #'.$event->entrevistaId,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
        };

        try {
            BitacoraEntry::query()->create([
                'usuario_id' => $payload['actor'],
                'sucursal_id' => $payload['branch'],
                'accion' => $payload['action'],
                'entidad' => $payload['entity'],
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

    private static function entityFor(string $tipo): string
    {
        return $tipo === 'entrevista'
            ? InscripcionesAtencionesModule::ENTITY_ATENCION_ENTREVISTA
            : InscripcionesAtencionesModule::ENTITY_ATENCION_DIAGNOSTICO;
    }
}
