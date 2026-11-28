<?php

namespace App\Modules\AgendaTurnos\Listeners;

use App\Modules\AgendaTurnos\Enums\AsistenciaEvento;
use App\Modules\AgendaTurnos\Enums\EstadoTurno;
use App\Modules\AgendaTurnos\Events\AsistenciaCorregida;
use App\Modules\AgendaTurnos\Events\AsistenciaRegistrada;
use App\Modules\AgendaTurnos\Exceptions\BitacoraAgendaNoDisponibleException;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\AgendaTurnos\Support\AsistenciaTurnosModule;
use Throwable;

/**
 * 07.04/RF-011, 07.04/RN-006, 07.04/RNF-012, 07.04/CA-008.
 *
 * La bitácora ES el historial del turno. Es síncrona y fail-closed: si
 * falla, la transacción de la Action se revierte.
 *
 * No guarda nombres de pacientes, servicios ni datos clínicos. Solo ids,
 * estados, el tipo de evento y el motivo cuando corresponde.
 */
final class RecordAsistenciaAudit
{
    public function handle(AsistenciaRegistrada|AsistenciaCorregida $event): void
    {
        try {
            $evento = $event instanceof AsistenciaRegistrada
                ? AsistenciaEvento::REGISTRO
                : $event->evento;

            $fueraVentana = $event instanceof AsistenciaCorregida && $event->fueraVentana;
            $motivo = $event instanceof AsistenciaCorregida ? $event->motivo : null;

            BitacoraEntry::query()->create([
                'usuario_id' => $event->actorId,
                'sucursal_id' => $event->sucursalId,
                'accion' => $event->estadoNuevo === EstadoTurno::ASISTIO ? 'ASISTIO' : 'FALTO',
                'entidad' => AsistenciaTurnosModule::ENTITY_TURNO,
                'entidad_id' => $event->turnoId,
                'datos_anteriores' => [
                    'estado' => $event->estadoAnterior->value,
                ],
                'datos_nuevos' => [
                    'estado' => $event->estadoNuevo->value,
                    'evento' => $evento->value,
                    'evento_label' => $evento->label(),
                    'fuera_ventana' => $fueraVentana,
                    'motivo_texto' => $motivo,
                    'origen' => $event->origenTipo,
                    'version' => $event->version,
                    'asistencia_registrada_en' => $event->asistenciaRegistradaEn,
                ],
                'motivo' => $motivo,
                'ip' => $event->ip,
                'modulo' => AsistenciaTurnosModule::AUDIT_MODULE,
                'resultado' => 'exito',
                'critico' => $fueraVentana,
            ]);
        } catch (Throwable $exception) {
            throw new BitacoraAgendaNoDisponibleException($exception);
        }
    }
}
