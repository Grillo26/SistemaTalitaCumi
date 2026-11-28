<?php

namespace App\Modules\AgendaTurnos\Listeners;

use App\Modules\AgendaTurnos\Events\DiagnosticoCancelado;
use App\Modules\AgendaTurnos\Events\EstadoGeneralDiagnosticoCambiado;
use App\Modules\AgendaTurnos\Events\TurnoDiagnosticoProgramado;
use App\Modules\AgendaTurnos\Exceptions\BitacoraAgendaNoDisponibleException;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\AgendaTurnos\Support\DiagnosticoAuditAction;
use App\Modules\AgendaTurnos\Support\DiagnosticosModule;
use Throwable;

/**
 * 07.05/RF-009 / 07.05/RN-007 / 07.05/RNF-012.
 *
 * Bitácora síncrona y fail-closed. No guarda nombres de pacientes, tutores ni
 * datos clínicos: solo ids, estados, fechas y el motivo cuando corresponde.
 */
final class RecordDiagnosticoAudit
{
    public function handle(
        EstadoGeneralDiagnosticoCambiado|TurnoDiagnosticoProgramado|DiagnosticoCancelado $event,
    ): void {
        try {
            match (true) {
                $event instanceof EstadoGeneralDiagnosticoCambiado => $this->estado($event),
                $event instanceof TurnoDiagnosticoProgramado => $this->programacion($event),
                default => $this->cancelacion($event),
            };
        } catch (Throwable $exception) {
            throw new BitacoraAgendaNoDisponibleException($exception);
        }
    }

    private function estado(EstadoGeneralDiagnosticoCambiado $event): void
    {
        $this->entrada([
            'usuario_id' => $event->actorId,
            'sucursal_id' => $event->sucursalId,
            'accion' => DiagnosticoAuditAction::ACTUALIZAR_ESTADO,
            'entidad' => DiagnosticosModule::ENTITY_DIAGNOSTICO,
            'entidad_id' => $event->diagnosticoId,
            'datos_anteriores' => [
                'estado_general' => $event->estadoAnterior->value,
                'estado_general_label' => $event->estadoAnterior->label(),
            ],
            'datos_nuevos' => [
                'estado_general' => $event->estadoNuevo->value,
                'estado_general_label' => $event->estadoNuevo->label(),
                'faltantes' => $event->faltantes,
                'origen' => $event->motivo === null ? 'sistema' : 'manual',
            ],
            'motivo' => $event->motivo,
            'ip' => $event->ip,
            'critico' => true,
        ]);
    }

    private function programacion(TurnoDiagnosticoProgramado $event): void
    {
        $this->entrada([
            'usuario_id' => $event->actorId,
            'sucursal_id' => $event->sucursalId,
            'accion' => DiagnosticoAuditAction::PROGRAMAR,
            'entidad' => DiagnosticosModule::ENTITY_TURNO,
            'entidad_id' => $event->turnoId,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'diagnostico_id' => $event->diagnosticoId,
                'paciente_id' => $event->pacienteId,
                'profesional_id' => $event->profesionalId,
                'fecha' => $event->fecha,
                'hora_inicio' => $event->horaInicio,
                'hora_fin' => $event->horaFin,
                'duracion_minutos' => $event->duracionMinutos,
                'estado' => 'programado',
            ],
            'motivo' => null,
            'ip' => $event->ip,
            'critico' => false,
        ]);
    }

    private function cancelacion(DiagnosticoCancelado $event): void
    {
        $this->entrada([
            'usuario_id' => $event->actorId,
            'sucursal_id' => $event->sucursalId,
            'accion' => DiagnosticoAuditAction::CANCELAR,
            'entidad' => DiagnosticosModule::ENTITY_DIAGNOSTICO,
            'entidad_id' => $event->diagnosticoId,
            'datos_anteriores' => [
                'estado_general' => $event->estadoAnterior->value,
                'estado_general_label' => $event->estadoAnterior->label(),
            ],
            'datos_nuevos' => [
                'estado_general' => 'cancelado',
                'paciente_id' => $event->pacienteId,
                'turnos_cancelados' => $event->turnosCancelados,
                'cantidad_cancelados' => count($event->turnosCancelados),
                'turnos_conservados' => $event->turnosConservados,
                'origen' => 'manual',
            ],
            'motivo' => $event->motivo,
            'ip' => $event->ip,
            'critico' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function entrada(array $datos): void
    {
        BitacoraEntry::query()->create([
            'usuario_id' => $datos['usuario_id'],
            'sucursal_id' => $datos['sucursal_id'],
            'accion' => $datos['accion'],
            'entidad' => $datos['entidad'],
            'entidad_id' => $datos['entidad_id'],
            'datos_anteriores' => $datos['datos_anteriores'],
            'datos_nuevos' => $datos['datos_nuevos'],
            'motivo' => $datos['motivo'],
            'ip' => $datos['ip'],
            'modulo' => DiagnosticosModule::AUDIT_MODULE,
            'resultado' => 'exito',
            'critico' => $datos['critico'],
        ]);
    }
}
