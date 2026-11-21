<?php

namespace App\Modules\Profesionales\Listeners;

use App\Modules\Profesionales\Events\Directorio\ProfesionalCreated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalEspecialidadesUpdated;
use App\Modules\Profesionales\Events\Directorio\ProfesionalStatusChanged;
use App\Modules\Profesionales\Events\Directorio\ProfesionalSucursalChanged;
use App\Modules\Profesionales\Events\Directorio\ProfesionalUpdated;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadCreated;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadStatusChanged;
use App\Modules\Profesionales\Events\Especialidades\EspecialidadUpdated;
use App\Modules\Profesionales\Exceptions\ProfesionalesAuditUnavailableException;
use App\Modules\Profesionales\Models\BitacoraEntry;
use App\Modules\Profesionales\Models\ProfesionalSucursal;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Registra en bitácora los eventos del módulo 03 (03/RF-D11, 03/RF-E10 / 03/RN-17).
 *
 * Las filas de profesional, especialidades y asignación de especialidades se
 * replican por cada sucursal activa del profesional para que Administración
 * las vea con su alcance. Las filas del catálogo global de especialidades y
 * los profesionales sin sucursal quedan con sucursal_id NULL.
 *
 * Es síncrono y fail-closed: si la bitácora no se puede escribir, la
 * operación se revierte.
 */
final class RecordProfesionalesAudit
{
    public function handle(
        ProfesionalCreated|ProfesionalUpdated|ProfesionalEspecialidadesUpdated|ProfesionalStatusChanged|
        ProfesionalSucursalChanged|EspecialidadCreated|EspecialidadUpdated|EspecialidadStatusChanged $event,
    ): void {
        $payload = match (true) {
            $event instanceof ProfesionalCreated => [
                'entity' => 'profesional',
                'id' => $event->profesionalId,
                'action' => 'CREAR',
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
                'branchIds' => $this->activeBranchIds($event->profesionalId),
            ],
            $event instanceof ProfesionalUpdated => [
                'entity' => 'profesional',
                'id' => $event->profesionalId,
                'action' => 'ACTUALIZAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
                'branchIds' => $this->activeBranchIds($event->profesionalId),
            ],
            $event instanceof ProfesionalEspecialidadesUpdated => [
                'entity' => 'profesional_especialidad',
                'id' => $event->profesionalId,
                'action' => 'ACTUALIZAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
                'branchIds' => $this->activeBranchIds($event->profesionalId),
            ],
            $event instanceof ProfesionalStatusChanged => [
                'entity' => 'profesional',
                'id' => $event->profesionalId,
                'action' => $event->activo ? 'ACTIVAR' : 'DESACTIVAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->reason,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => ! $event->activo,
                'branchIds' => $this->activeBranchIds($event->profesionalId),
            ],
            $event instanceof ProfesionalSucursalChanged => [
                'entity' => 'profesional_sucursal',
                'id' => $event->profesionalId,
                'action' => $event->action,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->reason,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => $event->action === 'DESACTIVAR',
                'branchIds' => [$event->sucursalId],
            ],
            $event instanceof EspecialidadCreated => [
                'entity' => 'especialidad',
                'id' => $event->especialidadId,
                'action' => 'CREAR',
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
                'branchIds' => [null],
            ],
            $event instanceof EspecialidadUpdated => [
                'entity' => 'especialidad',
                'id' => $event->especialidadId,
                'action' => 'ACTUALIZAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => false,
                'branchIds' => [null],
            ],
            $event instanceof EspecialidadStatusChanged => [
                'entity' => 'especialidad',
                'id' => $event->especialidadId,
                'action' => $event->activo ? 'ACTIVAR' : 'DESACTIVAR',
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->reason,
                'actor' => $event->actorId,
                'ip' => $event->ip,
                'critical' => ! $event->activo,
                'branchIds' => [null],
            ],
        };

        $branchIds = $payload['branchIds'] === [] ? [null] : $payload['branchIds'];

        try {
            DB::transaction(function () use ($payload, $branchIds): void {
                foreach ($branchIds as $branchId) {
                    BitacoraEntry::query()->create([
                        'usuario_id' => $payload['actor'],
                        'sucursal_id' => $branchId,
                        'accion' => $payload['action'],
                        'entidad' => $payload['entity'],
                        'entidad_id' => $payload['id'],
                        'datos_anteriores' => $payload['before'],
                        'datos_nuevos' => $payload['after'],
                        'motivo' => $payload['reason'],
                        'ip' => $payload['ip'],
                        'modulo' => 'profesionales',
                        'resultado' => 'exito',
                        'critico' => $payload['critical'],
                    ]);
                }
            });
        } catch (Throwable $exception) {
            throw new ProfesionalesAuditUnavailableException($exception);
        }
    }

    /**
     * @return list<int>
     */
    private function activeBranchIds(int $profesionalId): array
    {
        return ProfesionalSucursal::query()
            ->join('sucursal', 'sucursal.id', '=', 'profesional_sucursal.sucursal_id')
            ->where('profesional_sucursal.profesional_id', $profesionalId)
            ->where('profesional_sucursal.activo', true)
            ->where('sucursal.activo', true)
            ->orderBy('profesional_sucursal.sucursal_id')
            ->pluck('profesional_sucursal.sucursal_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
