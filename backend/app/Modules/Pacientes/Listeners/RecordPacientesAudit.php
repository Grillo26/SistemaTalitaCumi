<?php

namespace App\Modules\Pacientes\Listeners;

use App\Modules\Pacientes\Events\Registro\PacienteCreated;
use App\Modules\Pacientes\Events\Registro\PacienteDuplicateConfirmed;
use App\Modules\Pacientes\Events\Registro\PacienteStatusChanged;
use App\Modules\Pacientes\Events\Registro\PacienteUpdated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorAssociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorDissociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorPrincipalChanged;
use App\Modules\Pacientes\Events\Tutores\TutorCreated;
use App\Modules\Pacientes\Events\Tutores\TutorUpdated;
use App\Modules\Pacientes\Exceptions\PacientesAuditUnavailableException;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Support\PacientesAuditAction;
use App\Modules\Pacientes\Support\PacientesModule;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Registra en bitácora los eventos del módulo 04 (04.01/RF-012, 04.01/RF-013).
 *
 * Síncrono y fail-closed: si la bitácora no se puede escribir, la operación
 * se revierte. Nunca guarda contenido clínico: solo snapshots no sensibles,
 * referencias y booleanos de presencia.
 */
final class RecordPacientesAudit
{
    public function handle(
        PacienteCreated|PacienteUpdated|PacienteStatusChanged|PacienteDuplicateConfirmed|
        TutorCreated|TutorUpdated|PacienteTutorAssociated|PacienteTutorDissociated|PacienteTutorPrincipalChanged $event,
    ): void {
        $payload = match (true) {
            $event instanceof PacienteCreated => [
                'entity' => 'paciente',
                'id' => $event->pacienteId,
                'action' => PacientesAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof PacienteUpdated => [
                'entity' => 'paciente',
                'id' => $event->pacienteId,
                'action' => PacientesAuditAction::UPDATE,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'campos_modificados' => $event->changedFields,
                    'campos_sensibles_modificados' => $event->sensitiveFields,
                ]),
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof PacienteStatusChanged => [
                'entity' => 'paciente',
                'id' => $event->pacienteId,
                'action' => PacientesAuditAction::STATUS_CHANGE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->motivo,
                'critical' => $event->critico,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof PacienteDuplicateConfirmed => [
                'entity' => 'paciente',
                'id' => $event->pacienteId,
                'action' => PacientesAuditAction::CONFIRM_DUPLICATE,
                'before' => null,
                'after' => [
                    'coincidencias_en_alcance' => $event->coincidenciasEnAlcance,
                    'hubo_coincidencias_fuera_de_alcance' => $event->huboFueraDeAlcance,
                ],
                'reason' => $event->motivo,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof TutorCreated => [
                'entity' => 'tutor',
                'id' => $event->tutorId,
                'action' => PacientesAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof TutorUpdated => [
                'entity' => 'tutor',
                'id' => $event->tutorId,
                'action' => PacientesAuditAction::UPDATE,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'campos_modificados' => $event->changedFields,
                ]),
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof PacienteTutorAssociated => [
                'entity' => 'paciente_tutor',
                'id' => $event->pacienteId,
                'action' => PacientesAuditAction::ASSOCIATE_TUTOR,
                'before' => null,
                'after' => [
                    'paciente_id' => $event->pacienteId,
                    'tutor_id' => $event->tutorId,
                    'es_principal' => $event->esPrincipal,
                ],
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof PacienteTutorDissociated => [
                'entity' => 'paciente_tutor',
                'id' => $event->pacienteId,
                'action' => PacientesAuditAction::DISSOCIATE_TUTOR,
                'before' => [
                    'paciente_id' => $event->pacienteId,
                    'tutor_id' => $event->tutorId,
                    'era_principal' => $event->eraPrincipal,
                ],
                'after' => null,
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof PacienteTutorPrincipalChanged => [
                'entity' => 'paciente_tutor',
                'id' => $event->pacienteId,
                'action' => PacientesAuditAction::CHANGE_PRINCIPAL_TUTOR,
                'before' => ['tutor_anterior_id' => $event->anteriorTutorId],
                'after' => [
                    'paciente_id' => $event->pacienteId,
                    'tutor_id' => $event->tutorId,
                    'es_principal' => true,
                ],
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
        };

        $branchIds = array_values(array_unique($payload['branchIds']));

        if ($branchIds === []) {
            $branchIds = [null];
        }

        try {
            DB::transaction(function () use ($payload, $branchIds, $event): void {
                foreach ($branchIds as $branchId) {
                    BitacoraEntry::query()->create([
                        'usuario_id' => $event->actorId,
                        'sucursal_id' => $branchId,
                        'accion' => $payload['action'],
                        'entidad' => $payload['entity'],
                        'entidad_id' => $payload['id'],
                        'datos_anteriores' => $payload['before'],
                        'datos_nuevos' => $payload['after'],
                        'motivo' => $payload['reason'],
                        'ip' => $event->ip,
                        'modulo' => PacientesModule::AUDIT_MODULE,
                        'resultado' => 'exito',
                        'critico' => $payload['critical'],
                    ]);
                }
            });
        } catch (Throwable $exception) {
            throw new PacientesAuditUnavailableException($exception);
        }
    }
}
