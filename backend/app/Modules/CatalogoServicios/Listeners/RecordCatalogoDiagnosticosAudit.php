<?php

namespace App\Modules\CatalogoServicios\Listeners;

use App\Modules\CatalogoServicios\Events\TipoDiagnosticoCreated;
use App\Modules\CatalogoServicios\Events\TipoDiagnosticoStatusChanged;
use App\Modules\CatalogoServicios\Events\TipoDiagnosticoUpdated;
use App\Modules\CatalogoServicios\Exceptions\CatalogoServiciosAuditUnavailableException;
use App\Modules\CatalogoServicios\Models\BitacoraEntry;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosAuditAction;
use App\Modules\CatalogoServicios\Support\CatalogoServiciosModule;
use Throwable;

/**
 * Bitácora de tipos de diagnóstico (RF-D10).
 */
final class RecordCatalogoDiagnosticosAudit
{
    public function handle(
        TipoDiagnosticoCreated|TipoDiagnosticoUpdated|TipoDiagnosticoStatusChanged $event,
    ): void {
        $payload = match (true) {
            $event instanceof TipoDiagnosticoCreated => [
                'entity' => 'tipo_diagnostico',
                'id' => $event->tipoDiagnosticoId,
                'action' => CatalogoServiciosAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TipoDiagnosticoUpdated => [
                'entity' => 'tipo_diagnostico',
                'id' => $event->tipoDiagnosticoId,
                'action' => CatalogoServiciosAuditAction::UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
            ],
            $event instanceof TipoDiagnosticoStatusChanged => [
                'entity' => 'tipo_diagnostico',
                'id' => $event->tipoDiagnosticoId,
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
