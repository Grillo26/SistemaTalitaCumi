<?php

namespace App\Modules\Pacientes\Listeners;

use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoRegistered;
use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoRevoked;
use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoTipoCreated;
use App\Modules\Pacientes\Events\Consentimiento\ConsentimientoTipoUpdated;
use App\Modules\Pacientes\Events\Contactos\ContactoEmergenciaCreated;
use App\Modules\Pacientes\Events\Contactos\ContactoEmergenciaDeleted;
use App\Modules\Pacientes\Events\Contactos\ContactoEmergenciaUpdated;
use App\Modules\Pacientes\Events\Ficha\FichaIngresoCreated;
use App\Modules\Pacientes\Events\Ficha\FichaIngresoStatusChanged;
use App\Modules\Pacientes\Events\Ficha\FichaIngresoUpdated;
use App\Modules\Pacientes\Events\Registro\PacienteCreated;
use App\Modules\Pacientes\Events\Registro\PacienteDuplicateConfirmed;
use App\Modules\Pacientes\Events\Registro\PacienteStatusChanged;
use App\Modules\Pacientes\Events\Registro\PacienteUpdated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorAssociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorDissociated;
use App\Modules\Pacientes\Events\Tutores\PacienteTutorPrincipalChanged;
use App\Modules\Pacientes\Events\Tutores\TutorCreated;
use App\Modules\Pacientes\Events\Tutores\TutorStatusChanged;
use App\Modules\Pacientes\Events\Tutores\TutorUpdated;
use App\Modules\Pacientes\Exceptions\PacientesAuditUnavailableException;
use App\Modules\Pacientes\Models\BitacoraEntry;
use App\Modules\Pacientes\Support\ConsentimientoAuditAction;
use App\Modules\Pacientes\Support\FichaAuditAction;
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
        TutorCreated|TutorUpdated|TutorStatusChanged|PacienteTutorAssociated|PacienteTutorDissociated|PacienteTutorPrincipalChanged|
        ContactoEmergenciaCreated|ContactoEmergenciaUpdated|ContactoEmergenciaDeleted|
        FichaIngresoCreated|FichaIngresoUpdated|FichaIngresoStatusChanged|
        ConsentimientoRegistered|ConsentimientoRevoked|ConsentimientoTipoCreated|ConsentimientoTipoUpdated $event,
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
            $event instanceof TutorStatusChanged => [
                'entity' => 'tutor',
                'id' => $event->tutorId,
                'action' => PacientesAuditAction::STATUS_CHANGE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->motivo,
                'critical' => $event->critico,
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
                    'parentesco_id' => $event->parentescoId,
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
                'reason' => $event->motivo,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof ContactoEmergenciaCreated => [
                'entity' => 'contacto_emergencia',
                'id' => $event->contactoId,
                'action' => PacientesAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof ContactoEmergenciaUpdated => [
                'entity' => 'contacto_emergencia',
                'id' => $event->contactoId,
                'action' => PacientesAuditAction::UPDATE,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'campos_modificados' => $event->changedFields,
                ]),
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof ContactoEmergenciaDeleted => [
                'entity' => 'contacto_emergencia',
                'id' => $event->contactoId,
                'action' => PacientesAuditAction::DELETE,
                'before' => $event->snapshot,
                'after' => null,
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof FichaIngresoCreated => [
                'entity' => 'ficha_ingreso',
                'id' => $event->fichaId,
                'action' => FichaAuditAction::CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof FichaIngresoUpdated => [
                'entity' => 'ficha_ingreso',
                'id' => $event->fichaId,
                'action' => FichaAuditAction::UPDATE,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'campos_modificados' => $event->changedFields,
                ]),
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof FichaIngresoStatusChanged => [
                'entity' => 'ficha_ingreso',
                'id' => $event->fichaId,
                'action' => $event->accion,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => $event->motivo,
                'critical' => $event->critico,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof ConsentimientoRegistered => [
                'entity' => 'consentimiento',
                'id' => $event->consentimientoId,
                'action' => $event->resultado === 'otorgado'
                    ? ConsentimientoAuditAction::GRANT
                    : ConsentimientoAuditAction::DENY,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'critical' => false,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof ConsentimientoRevoked => [
                'entity' => 'consentimiento',
                'id' => $event->consentimientoId,
                'action' => ConsentimientoAuditAction::REVOKE,
                'before' => ['consentimiento_original_id' => $event->originalId],
                'after' => $event->snapshot,
                'reason' => null,
                'critical' => true,
                'branchIds' => $event->branchIds,
            ],
            $event instanceof ConsentimientoTipoCreated => [
                'entity' => 'consentimiento_tipo',
                'id' => $event->tipoId,
                'action' => ConsentimientoAuditAction::CATALOG_CREATE,
                'before' => null,
                'after' => $event->snapshot,
                'reason' => null,
                'critical' => false,
                'branchIds' => [],
            ],
            $event instanceof ConsentimientoTipoUpdated => [
                'entity' => 'consentimiento_tipo',
                'id' => $event->tipoId,
                'action' => ConsentimientoAuditAction::CATALOG_UPDATE,
                'before' => $event->before,
                'after' => $event->after,
                'reason' => null,
                'critical' => false,
                'branchIds' => [],
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
