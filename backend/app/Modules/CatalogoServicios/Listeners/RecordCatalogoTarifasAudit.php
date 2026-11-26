<?php

namespace App\Modules\CatalogoServicios\Listeners;

use App\Modules\CatalogoServicios\Events\CategoriaTarifaCreated;
use App\Modules\CatalogoServicios\Events\CategoriaTarifaStatusChanged;
use App\Modules\CatalogoServicios\Events\CategoriaTarifaUpdated;
use App\Modules\CatalogoServicios\Events\TarifaCreated;
use App\Modules\CatalogoServicios\Events\TarifaUpdated;
use App\Modules\CatalogoServicios\Events\TarifaVigenciaClosed;
use App\Modules\CatalogoServicios\Exceptions\CatalogoServiciosAuditUnavailableException;
use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAuditAction;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosModule;
use Throwable;

/**
 * Bitácora de tarifas y categorías (RF-T15).
 *
 * Misma tabla y convención que el submódulo 01. La sucursal de una tarifa
 * queda en `bitacora.sucursal_id`; las categorías y tarifas globales usan NULL.
 */
final class RecordCatalogoTarifasAudit
{
    public function handle(
        CategoriaTarifaCreated|CategoriaTarifaUpdated|CategoriaTarifaStatusChanged|
        TarifaCreated|TarifaUpdated|TarifaVigenciaClosed $event,
    ): void {
        $payload = match (true) {
            $event instanceof CategoriaTarifaCreated => [
                'entity' => 'categoria_tarifa',
                'id' => $event->categoriaTarifaId,
                'branch' => null,
                'action' => CatalogoServiciosAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof CategoriaTarifaUpdated => [
                'entity' => 'categoria_tarifa',
                'id' => $event->categoriaTarifaId,
                'branch' => null,
                'action' => CatalogoServiciosAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof CategoriaTarifaStatusChanged => [
                'entity' => 'categoria_tarifa',
                'id' => $event->categoriaTarifaId,
                'branch' => null,
                'action' => $event->activo
                    ? CatalogoServiciosAuditAction::ACTIVATE
                    : CatalogoServiciosAuditAction::DEACTIVATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->motivo,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TarifaCreated => [
                'entity' => 'tarifa',
                'id' => $event->tarifaId,
                'branch' => $event->snapshot['sucursal_id'] ?? null,
                'action' => CatalogoServiciosAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TarifaUpdated => [
                'entity' => 'tarifa',
                'id' => $event->tarifaId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => CatalogoServiciosAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TarifaVigenciaClosed => [
                'entity' => 'tarifa',
                'id' => $event->tarifaId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => CatalogoServiciosAuditAction::CLOSE_VIGENCIA,
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
                'sucursal_id' => $payload['branch'],
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
