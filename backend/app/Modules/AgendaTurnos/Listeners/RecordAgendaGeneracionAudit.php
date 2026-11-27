<?php

namespace App\Modules\AgendaTurnos\Listeners;

use App\Modules\AgendaTurnos\Events\FeriadoActualizado;
use App\Modules\AgendaTurnos\Events\FeriadoCreado;
use App\Modules\AgendaTurnos\Events\FeriadoDesactivado;
use App\Modules\AgendaTurnos\Events\ProfesionalAsignadoALote;
use App\Modules\AgendaTurnos\Events\TurnosGenerados;
use App\Modules\AgendaTurnos\Events\TurnosRegenerados;
use App\Modules\AgendaTurnos\Exceptions\BitacoraAgendaNoDisponibleException;
use App\Modules\AgendaTurnos\Models\BitacoraEntry;
use App\Modules\AgendaTurnos\Support\AgendaAuditAction;
use App\Modules\AgendaTurnos\Support\AgendaTurnosModule;
use Throwable;

/**
 * [Programación de Turnos] RF-012 / RNF-012 / CA-010.
 *
 * Bitácora síncrona y fail-closed. Se ejecuta dentro de la transacción de la
 * Action: si la generación se revierte, no queda registro; si la bitácora
 * falla, la generación también se revierte.
 */
final class RecordAgendaGeneracionAudit
{
    public function handle(
        TurnosGenerados|TurnosRegenerados|ProfesionalAsignadoALote|FeriadoCreado|FeriadoActualizado|FeriadoDesactivado $event,
    ): void {
        $payload = match (true) {
            $event instanceof TurnosGenerados => [
                'entity' => AgendaTurnosModule::ENTITY_LOTE_GENERACION,
                'entity_id' => $event->loteId,
                'branch' => $event->sucursalId,
                'action' => AgendaAuditAction::GENERAR,
                'before' => null,
                'after' => [
                    'lote_id' => $event->loteId,
                    'tipo_origen' => $event->tipoOrigen->value,
                    'origen_id' => $event->origenId,
                    'cantidad_creados' => $event->cantidadCreados,
                    'turno_ids' => $event->turnoIds,
                    'advertencias_confirmadas' => $event->advertencias,
                ],
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof TurnosRegenerados => [
                'entity' => AgendaTurnosModule::ENTITY_LOTE_GENERACION,
                'entity_id' => $event->loteId,
                'branch' => $event->sucursalId,
                'action' => AgendaAuditAction::REGENERAR,
                'before' => null,
                'after' => [
                    'lote_id' => $event->loteId,
                    'tipo_origen' => $event->tipoOrigen->value,
                    'origen_id' => $event->origenId,
                    'cantidad_creados' => $event->cantidadCreados,
                    'cantidad_cancelados' => $event->cantidadCancelados,
                    'cantidad_preservados' => $event->cantidadPreservados,
                    'turnos_creados_ids' => $event->turnosCreadosIds,
                    'turnos_cancelados_ids' => $event->turnosCanceladosIds,
                    'advertencias_confirmadas' => $event->advertencias,
                ],
                'reason' => 'Regeneración de turnos',
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => true,
            ],
            $event instanceof ProfesionalAsignadoALote => [
                'entity' => AgendaTurnosModule::ENTITY_TURNO,
                'entity_id' => $event->loteId,
                'branch' => $event->sucursalId,
                'action' => AgendaAuditAction::ASIGNAR_PROFESIONAL,
                'before' => null,
                'after' => [
                    'lote_id' => $event->loteId,
                    'profesional_id' => $event->profesionalId,
                    'turno_ids' => $event->turnoIds,
                ],
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof FeriadoCreado => [
                'entity' => AgendaTurnosModule::ENTITY_FERIADO,
                'entity_id' => $event->feriadoId,
                'branch' => $event->sucursalId,
                'action' => AgendaAuditAction::CREAR_FERIADO,
                'before' => null,
                'after' => [
                    'fecha' => $event->fecha,
                    'descripcion' => $event->descripcion,
                    'sucursal_id' => $event->sucursalId,
                    'activo' => true,
                ],
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            $event instanceof FeriadoActualizado => [
                'entity' => AgendaTurnosModule::ENTITY_FERIADO,
                'entity_id' => $event->feriadoId,
                'branch' => $event->after['sucursal_id'] ?? null,
                'action' => AgendaAuditAction::ACTUALIZAR_FERIADO,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
            ],
            default => [
                'entity' => AgendaTurnosModule::ENTITY_FERIADO,
                'entity_id' => $event->feriadoId,
                'branch' => $event->before['sucursal_id'] ?? null,
                'action' => AgendaAuditAction::DESACTIVAR_FERIADO,
                'before' => $event->before,
                'after' => ['activo' => false],
                'reason' => 'Desactivación de feriado',
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
                'modulo' => AgendaTurnosModule::AUDIT_MODULE,
                'resultado' => 'exito',
                'critico' => $payload['critical'],
            ]);
        } catch (Throwable $exception) {
            throw new BitacoraAgendaNoDisponibleException($exception);
        }
    }
}
