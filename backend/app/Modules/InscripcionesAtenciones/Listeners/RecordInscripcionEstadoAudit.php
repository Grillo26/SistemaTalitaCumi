<?php

namespace App\Modules\InscripcionesAtenciones\Listeners;

use App\Modules\InscripcionesAtenciones\Events\InscripcionPausada;
use App\Modules\InscripcionesAtenciones\Events\InscripcionReactivada;
use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionAuditUnavailableException;
use App\Modules\InscripcionesAtenciones\Models\BitacoraEntry;
use App\Modules\InscripcionesAtenciones\Support\InscripcionAuditAction;
use App\Modules\InscripcionesAtenciones\Support\InscripcionesAtencionesModule;
use Throwable;

/**
 * [Estados Paciente e Inscripción] RF-009 / RNF-011.
 *
 * Bitácora de pausa y reactivación. El retiro y la renovación ya tienen sus
 * propios listeners; no se duplican.
 */
final class RecordInscripcionEstadoAudit
{
    public function handle(InscripcionPausada|InscripcionReactivada $event): void
    {
        $esPausa = $event instanceof InscripcionPausada;
        $motivo = $esPausa
            ? trim($event->motivoCodigo.($event->motivoTexto ? ': '.$event->motivoTexto : ''))
            : null;

        try {
            BitacoraEntry::query()->create([
                'usuario_id' => $event->actorId,
                'sucursal_id' => $event->sucursalId,
                'accion' => $esPausa
                    ? InscripcionAuditAction::PAUSE
                    : InscripcionAuditAction::REACTIVATE,
                'entidad' => $event->tipo->value,
                'entidad_id' => $event->entidadId,
                'datos_anteriores' => $event->before,
                'datos_nuevos' => array_merge($event->after, [
                    'motivo_codigo' => $esPausa ? $event->motivoCodigo : null,
                    'motivo_detalle' => $esPausa ? $event->motivoTexto : null,
                ]),
                'motivo' => $motivo,
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
