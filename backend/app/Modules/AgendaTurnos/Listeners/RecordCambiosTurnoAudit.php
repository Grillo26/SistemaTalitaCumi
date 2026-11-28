<?php

namespace App\Modules\AgendaTurnos\Listeners;

use App\Modules\AgendaTurnos\Enums\AccionCambioTurno;
use App\Modules\AgendaTurnos\Events\TurnoCancelado;
use App\Modules\AgendaTurnos\Events\TurnoReprogramado;
use App\Modules\AgendaTurnos\Events\TurnosCanceladosMasivamente;
use App\Modules\AgendaTurnos\Exceptions\BitacoraAgendaNoDisponibleException;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\AgendaTurnos\Support\AgendaTurnosModule;
use App\Modules\AgendaTurnos\Support\CambiosTurnosModule;
use Throwable;

/**
 * [Reprogramación y Cancelación] RF-009 / RF-010 / RN-007 / RNF-012 / CA-006 /
 * CA-008.
 *
 * La bitácora ES el historial del turno (misma decisión que Estados). Es
 * síncrona y fail-closed: si falla, la transacción de la Action se revierte.
 */
final class RecordCambiosTurnoAudit
{
    public function handle(
        TurnoReprogramado|TurnoCancelado|TurnosCanceladosMasivamente $event,
    ): void {
        try {
            match (true) {
                $event instanceof TurnoReprogramado => $this->reprogramacion($event),
                $event instanceof TurnoCancelado => $this->cancelacion($event),
                default => $this->lote($event),
            };
        } catch (Throwable $exception) {
            throw new BitacoraAgendaNoDisponibleException($exception);
        }
    }

    private function reprogramacion(TurnoReprogramado $event): void
    {
        $motivo = [
            'motivo_codigo' => $event->motivo->codigo,
            'motivo_nombre' => $event->motivo->nombre,
            'motivo_texto' => $event->original['motivo_texto'] ?? null,
        ];

        $this->entrada([
            'usuario_id' => $event->actorId,
            'sucursal_id' => $event->sucursalId,
            'accion' => AccionCambioTurno::REPROGRAMAR->value,
            'entidad' => CambiosTurnosModule::ENTITY_TURNO,
            'entidad_id' => $event->turnoOriginalId,
            'datos_anteriores' => [
                'estado' => $event->original['estado'],
                'fecha' => $event->original['fecha'],
                'hora_inicio' => $event->original['hora_inicio'],
                'profesional_id' => $event->original['profesional_id'],
                'sala_id' => $event->original['sala_id'],
            ],
            'datos_nuevos' => [
                'estado' => 'reprogramado',
                'turno_nuevo_id' => $event->turnoNuevoId,
                'fecha' => $event->nuevo['fecha'],
                'hora_inicio' => $event->nuevo['hora_inicio'],
                'profesional_id' => $event->nuevo['profesional_id'],
                'sala_id' => $event->nuevo['sala_id'],
                'advertencias' => $event->advertencias,
                'origen' => 'manual',
                ...$motivo,
            ],
            'motivo' => $event->motivo->nombre,
            'ip' => $event->ip,
            'critico' => false,
        ]);

        $this->entrada([
            'usuario_id' => $event->actorId,
            'sucursal_id' => $event->sucursalId,
            'accion' => AccionCambioTurno::CREAR_POR_REPROGRAMACION->value,
            'entidad' => CambiosTurnosModule::ENTITY_TURNO,
            'entidad_id' => $event->turnoNuevoId,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'estado' => 'programado',
                'turno_origen_id' => $event->turnoOriginalId,
                'fecha' => $event->nuevo['fecha'],
                'hora_inicio' => $event->nuevo['hora_inicio'],
                'profesional_id' => $event->nuevo['profesional_id'],
                'sala_id' => $event->nuevo['sala_id'],
                'origen' => 'sistema',
                ...$motivo,
            ],
            'motivo' => $event->motivo->nombre,
            'ip' => $event->ip,
            'critico' => false,
        ]);
    }

    private function cancelacion(TurnoCancelado $event): void
    {
        $this->entrada([
            'usuario_id' => $event->actorId,
            'sucursal_id' => $event->sucursalId,
            'accion' => $event->masivo
                ? AccionCambioTurno::CANCELAR_MASIVO->value
                : AccionCambioTurno::CANCELAR->value,
            'entidad' => CambiosTurnosModule::ENTITY_TURNO,
            'entidad_id' => $event->turnoId,
            'datos_anteriores' => [
                'estado' => 'programado',
                'fecha' => $event->fecha,
                'hora_inicio' => $event->horaInicio,
            ],
            'datos_nuevos' => [
                'estado' => 'cancelado',
                'fecha' => $event->fecha,
                'hora_inicio' => $event->horaInicio,
                'motivo_codigo' => $event->motivo->codigo,
                'motivo_nombre' => $event->motivo->nombre,
                'motivo_texto' => $event->motivoTexto,
                'consume_saldo' => $event->consumeSaldo,
                'advertencias' => $event->advertencias,
                'lote_id' => $event->loteId,
                'origen' => 'manual',
            ],
            'motivo' => $event->motivo->nombre,
            'ip' => $event->ip,
            'critico' => true,
        ]);
    }

    private function lote(TurnosCanceladosMasivamente $event): void
    {
        $this->entrada([
            'usuario_id' => $event->actorId,
            'sucursal_id' => $event->sucursales[0] ?? null,
            'accion' => AccionCambioTurno::CANCELAR_MASIVO->value,
            'entidad' => CambiosTurnosModule::ENTITY_LOTE_CANCELACION,
            'entidad_id' => null,
            'datos_anteriores' => null,
            'datos_nuevos' => [
                'lote_id' => $event->loteId,
                'paciente_id' => $event->pacienteId,
                'entidades' => $event->entidades,
                'turno_ids' => $event->turnoIds,
                'cantidad' => count($event->turnoIds),
                'sucursales' => $event->sucursales,
                'motivo_codigo' => $event->motivo->codigo,
                'motivo_nombre' => $event->motivo->nombre,
                'motivo_texto' => $event->motivoTexto,
            ],
            'motivo' => $event->motivo->nombre,
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
            'modulo' => AgendaTurnosModule::AUDIT_MODULE,
            'resultado' => 'exito',
            'critico' => $datos['critico'],
        ]);
    }
}
