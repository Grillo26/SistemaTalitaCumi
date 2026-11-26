<?php

namespace App\Modules\InscripcionesAtenciones\Listeners;

use App\Modules\InscripcionesAtenciones\Events\RenovacionMultipleEjecutada;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionAuditUnavailableException;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAuditAction;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use Throwable;

/**
 * Registra el lote de una renovación múltiple.
 *
 * Las renovaciones individuales ya se auditan por sus propios eventos; este
 * registro solo agrega la trazabilidad del lote.
 */
final class RecordRenovacionMultipleAudit
{
    public function handle(RenovacionMultipleEjecutada $event): void
    {
        try {
            BitacoraEntry::query()->create([
                'usuario_id' => $event->actorId,
                'sucursal_id' => count($event->sucursalIds) === 1 ? $event->sucursalIds[0] : null,
                'accion' => InscripcionAuditAction::RENEW,
                'entidad' => InscripcionesAtencionesModule::ENTITY_RENOVACION_MULTIPLE,
                'entidad_id' => null,
                'datos_anteriores' => null,
                'datos_nuevos' => [
                    'lote_id' => $event->loteId,
                    'paciente_id' => $event->pacienteId,
                    'items' => $event->items,
                ],
                'motivo' => 'Renovación múltiple de vigencias',
                'ip' => $event->ip,
                'modulo' => InscripcionesAtencionesModule::AUDIT_MODULE,
                'resultado' => 'exito',
                'critico' => false,
            ]);
        } catch (Throwable $exception) {
            throw new InscripcionAuditUnavailableException($exception);
        }
    }
}
