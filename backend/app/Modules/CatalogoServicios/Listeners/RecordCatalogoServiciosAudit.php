<?php

namespace App\Modules\CatalogoServicios\Listeners;

use App\Modules\CatalogoServicios\Events\AreaColorChanged;
use App\Modules\CatalogoServicios\Events\AreaCreated;
use App\Modules\CatalogoServicios\Events\AreaStatusChanged;
use App\Modules\CatalogoServicios\Events\AreaUpdated;
use App\Modules\CatalogoServicios\Events\ServicioCreated;
use App\Modules\CatalogoServicios\Events\ServicioStatusChanged;
use App\Modules\CatalogoServicios\Events\ServicioUpdated;
use App\Modules\CatalogoServicios\Events\TipoServicioCreated;
use App\Modules\CatalogoServicios\Events\TipoServicioStatusChanged;
use App\Modules\CatalogoServicios\Events\TipoServicioUpdated;
use App\Modules\CatalogoServicios\Exceptions\CatalogoServiciosAuditUnavailableException;
use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAuditAction;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosModule;
use Throwable;

/**
 * Bitácora del catálogo (RF-C14).
 *
 * Síncrona y fail-closed: si no se puede escribir, la operación se revierte.
 * Las áreas, servicios y tipos son globales: `sucursal_id = NULL`.
 */
final class RecordCatalogoServiciosAudit
{
    public function handle(
        AreaCreated|AreaUpdated|AreaColorChanged|AreaStatusChanged|
        ServicioCreated|ServicioUpdated|ServicioStatusChanged|
        TipoServicioCreated|TipoServicioUpdated|TipoServicioStatusChanged $event,
    ): void {
        $payload = match (true) {
            $event instanceof AreaCreated => [
                'entity' => 'area',
                'id' => $event->areaId,
                'action' => CatalogoServiciosAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof AreaUpdated => [
                'entity' => 'area',
                'id' => $event->areaId,
                'action' => CatalogoServiciosAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof AreaColorChanged => [
                'entity' => 'area',
                'id' => $event->areaId,
                'action' => CatalogoServiciosAuditAction::COLOR_CHANGE,
                'before' => ['color' => $event->beforeColor],
                'after' => ['color' => $event->afterColor],
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof AreaStatusChanged => [
                'entity' => 'area',
                'id' => $event->areaId,
                'action' => $event->activo
                    ? CatalogoServiciosAuditAction::ACTIVATE
                    : CatalogoServiciosAuditAction::DEACTIVATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->motivo,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof ServicioCreated => [
                'entity' => 'servicio',
                'id' => $event->servicioId,
                'action' => CatalogoServiciosAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof ServicioUpdated => [
                'entity' => 'servicio',
                'id' => $event->servicioId,
                'action' => CatalogoServiciosAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof ServicioStatusChanged => [
                'entity' => 'servicio',
                'id' => $event->servicioId,
                'action' => $event->activo
                    ? CatalogoServiciosAuditAction::ACTIVATE
                    : CatalogoServiciosAuditAction::DEACTIVATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->motivo,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TipoServicioCreated => [
                'entity' => 'tipo_servicio',
                'id' => $event->tipoServicioId,
                'action' => CatalogoServiciosAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TipoServicioUpdated => [
                'entity' => 'tipo_servicio',
                'id' => $event->tipoServicioId,
                'action' => CatalogoServiciosAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TipoServicioStatusChanged => [
                'entity' => 'tipo_servicio',
                'id' => $event->tipoServicioId,
                'action' => $event->activo
                    ? CatalogoServiciosAuditAction::ACTIVATE
                    : CatalogoServiciosAuditAction::DEACTIVATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->motivo,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
        };

        try {
            BitacoraEntry::query()->create([
                'usuario_id' => $payload['actor'],
                'sucursal_id' => null,
                'accion' => $payload['action'],
                'entidad' => $payload['entity'],
                'entidad_id' => $payload['id'],
                'datos_anteriores' => $payload['before'],
                'datos_nuevos' => $payload['after'],
                'motivo' => $payload['reason'],
                'ip' => $payload['ip'],
                'modulo' => CatalogoServiciosModule::AUDIT_MODULE,
                'resultado' => 'exito',
                'critico' => false,
            ]);
        } catch (Throwable $exception) {
            throw new CatalogoServiciosAuditUnavailableException($exception);
        }
    }
}
