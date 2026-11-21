<?php

namespace App\Modules\Seguridad\Listeners;

use App\Models\User;
use App\Modules\Organizacion\Support\BranchPermission;
use App\Modules\Seguridad\Enums\AuditAction;
use App\Modules\Seguridad\Enums\AuditModule;
use App\Modules\Seguridad\Enums\AuditResult;
use App\Modules\Seguridad\Events\RolePermissionsUpdated;
use App\Modules\Seguridad\Events\UserCreated;
use App\Modules\Seguridad\Events\UserLocked;
use App\Modules\Seguridad\Events\UserLoggedIn;
use App\Modules\Seguridad\Events\UserLoggedOut;
use App\Modules\Seguridad\Events\UserLoginFailed;
use App\Modules\Seguridad\Events\UserPasswordChanged;
use App\Modules\Seguridad\Events\UserPasswordReset;
use App\Modules\Seguridad\Events\UserSessionExpired;
use App\Modules\Seguridad\Events\UserSessionsClosed;
use App\Modules\Seguridad\Events\UserStatusChanged;
use App\Modules\Seguridad\Events\UserUnlocked;
use App\Modules\Seguridad\Events\UserUpdated;
use App\Modules\Seguridad\Exceptions\AuditUnavailableException;
use App\Modules\Seguridad\Models\BitacoraEntry;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Registra en bitácora los eventos de seguridad (02/RF-A12, 02/RF-B01).
 *
 * Es síncrono y fail-closed: si la bitácora no se puede escribir, la
 * operación se aborta (RT-12). Nunca escribe contraseñas, hashes ni tokens.
 *
 * Los eventos de usuario se replican por cada sucursal activa del usuario
 * afectado para que Administración pueda consultarlos con su alcance. Los
 * usuarios con alcance global se registran una sola vez como globales. La
 * matriz de permisos sigue siendo global y solo la ve el Superadmin.
 */
final class RecordSecurityAudit
{
    public function handle(
        UserLoggedIn|UserLoginFailed|UserLoggedOut|UserPasswordChanged|UserLocked|
        UserCreated|UserUpdated|UserStatusChanged|UserPasswordReset|UserUnlocked|UserSessionsClosed|
        UserSessionExpired|RolePermissionsUpdated $event,
    ): void {
        $payload = match (true) {
            $event instanceof UserLoggedIn => [
                'usuario_id' => $event->userId,
                'accion' => AuditAction::LOGIN,
                'entidad' => 'sesion',
                'entidad_id' => $event->tokenId,
                'before' => null,
                'after' => array_filter([
                    'expira_en' => $event->expiresAt,
                    'ultimo_acceso_anterior' => $event->previousLastAccess,
                    'sesiones_reemplazadas' => $event->replacedSessions ?: null,
                    'user_agent' => $event->userAgent,
                ], static fn (mixed $value): bool => $value !== null),
                'motivo' => null,
                'ip' => $event->ip,
                'modulo' => AuditModule::AUTENTICACION,
                'resultado' => AuditResult::EXITO,
                'critico' => false,
            ],
            $event instanceof UserLoginFailed => [
                'usuario_id' => $event->userId,
                'accion' => AuditAction::LOGIN_FAILED,
                'entidad' => 'sesion',
                'entidad_id' => null,
                'before' => null,
                'after' => ['identificador' => $event->identifier],
                'motivo' => $event->reason,
                'ip' => $event->ip,
                'modulo' => AuditModule::AUTENTICACION,
                'resultado' => $event->result,
                'critico' => false,
            ],
            $event instanceof UserLoggedOut => [
                'usuario_id' => $event->userId,
                'accion' => AuditAction::LOGOUT,
                'entidad' => 'sesion',
                'entidad_id' => $event->tokenId,
                'before' => null,
                'after' => null,
                'motivo' => null,
                'ip' => $event->ip,
                'modulo' => AuditModule::AUTENTICACION,
                'resultado' => AuditResult::EXITO,
                'critico' => false,
            ],
            $event instanceof UserPasswordChanged => [
                'usuario_id' => $event->userId,
                'accion' => AuditAction::PASSWORD_CHANGED,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => null,
                'after' => [
                    'password_actualizada_en' => now()->toIso8601String(),
                    'sesiones_revocadas' => $event->revokedSessions,
                    'cambiado_por' => $event->changedBy,
                ],
                'motivo' => null,
                'ip' => $event->ip,
                'modulo' => AuditModule::AUTENTICACION,
                'resultado' => AuditResult::EXITO,
                'critico' => false,
            ],
            $event instanceof UserLocked => [
                'usuario_id' => $event->userId,
                'accion' => AuditAction::LOCK,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => null,
                'after' => [
                    'intentos_fallidos' => $event->failedAttempts,
                    'bloqueado_hasta' => $event->blockedUntil,
                ],
                'motivo' => 'intentos_fallidos',
                'ip' => $event->ip,
                'modulo' => AuditModule::AUTENTICACION,
                'resultado' => AuditResult::DENEGADO,
                'critico' => true,
            ],
            $event instanceof UserCreated => [
                'usuario_id' => $event->actorId,
                'accion' => AuditAction::CREATE,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => null,
                'after' => $event->snapshot,
                'motivo' => null,
                'ip' => $event->ip,
                'modulo' => AuditModule::USUARIOS,
                'resultado' => AuditResult::EXITO,
                'critico' => false,
            ],
            $event instanceof UserUpdated => [
                'usuario_id' => $event->actorId,
                'accion' => AuditAction::UPDATE,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'campos_cambiados' => $event->changedFields,
                    'rol_cambiado' => $event->roleChanged,
                    'sesiones_invalidadas' => $event->sessionsInvalidated,
                ]),
                'motivo' => null,
                'ip' => $event->ip,
                'modulo' => AuditModule::USUARIOS,
                'resultado' => AuditResult::EXITO,
                'critico' => $event->roleChanged,
            ],
            $event instanceof UserStatusChanged => [
                'usuario_id' => $event->actorId,
                'accion' => $event->active ? AuditAction::ACTIVATE : AuditAction::DEACTIVATE,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'sesiones_invalidadas' => $event->sessionsInvalidated,
                ]),
                'motivo' => $event->reason,
                'ip' => $event->ip,
                'modulo' => AuditModule::USUARIOS,
                'resultado' => AuditResult::EXITO,
                'critico' => ! $event->active,
            ],
            $event instanceof UserPasswordReset => [
                'usuario_id' => $event->actorId,
                'accion' => AuditAction::PASSWORD_RESET,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => $event->before,
                'after' => array_merge($event->after, [
                    'sesiones_invalidadas' => $event->sessionsInvalidated,
                ]),
                'motivo' => null,
                'ip' => $event->ip,
                'modulo' => AuditModule::USUARIOS,
                'resultado' => AuditResult::EXITO,
                'critico' => true,
            ],
            $event instanceof UserUnlocked => [
                'usuario_id' => $event->actorId,
                'accion' => AuditAction::UNLOCK,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => $event->before,
                'after' => $event->after,
                'motivo' => $event->reason,
                'ip' => $event->ip,
                'modulo' => AuditModule::USUARIOS,
                'resultado' => AuditResult::EXITO,
                'critico' => true,
            ],
            $event instanceof UserSessionsClosed => [
                'usuario_id' => $event->actorId,
                'accion' => AuditAction::SESSIONS_INVALIDATED,
                'entidad' => 'usuario',
                'entidad_id' => $event->userId,
                'before' => null,
                'after' => [
                    'sesiones_cerradas' => $event->sessionsClosed,
                ],
                'motivo' => $event->reason,
                'ip' => $event->ip,
                'modulo' => AuditModule::USUARIOS,
                'resultado' => AuditResult::EXITO,
                'critico' => true,
            ],
            $event instanceof UserSessionExpired => [
                'usuario_id' => $event->userId,
                'accion' => AuditAction::SESSION_EXPIRED,
                'entidad' => 'sesion',
                'entidad_id' => $event->tokenId,
                'before' => null,
                'after' => null,
                'motivo' => 'inactividad',
                'ip' => $event->ip,
                'modulo' => AuditModule::AUTENTICACION,
                'resultado' => AuditResult::EXITO,
                'critico' => false,
            ],
            $event instanceof RolePermissionsUpdated => [
                'usuario_id' => $event->actorId,
                'accion' => AuditAction::EDIT_PERMISSIONS,
                'entidad' => 'matriz_permisos',
                'entidad_id' => $event->roleId,
                'before' => [
                    'permisos' => $event->before,
                    'version' => $event->beforeVersion,
                ],
                'after' => [
                    'permisos' => $event->after,
                    'version' => $event->version,
                    'linea_base' => $event->baseline,
                ],
                'motivo' => $event->motivo,
                'ip' => $event->ip,
                'modulo' => AuditModule::ROLES,
                'resultado' => AuditResult::EXITO,
                'critico' => ! $event->baseline,
            ],
        };

        $affectedUserId = $this->affectedUserId($event);
        $branchIds = $this->branchIdsFor($affectedUserId);
        $sucursalIds = $branchIds === [] ? [null] : $branchIds;

        try {
            DB::transaction(function () use ($payload, $sucursalIds): void {
                foreach ($sucursalIds as $sucursalId) {
                    BitacoraEntry::query()->create([
                        'usuario_id' => $payload['usuario_id'],
                        'sucursal_id' => $sucursalId,
                        'accion' => $payload['accion'],
                        'entidad' => $payload['entidad'],
                        'entidad_id' => $payload['entidad_id'],
                        'datos_anteriores' => $payload['before'],
                        'datos_nuevos' => $payload['after'],
                        'motivo' => $payload['motivo'],
                        'ip' => $payload['ip'],
                        'modulo' => $payload['modulo']->value,
                        'resultado' => $payload['resultado']->value,
                        'critico' => $payload['critico'],
                    ]);
                }
            });
        } catch (Throwable $exception) {
            throw new AuditUnavailableException($exception);
        }
    }

    private function affectedUserId(object $event): ?int
    {
        if ($event instanceof RolePermissionsUpdated) {
            return null;
        }

        return $event->userId;
    }

    /**
     * @return list<int>
     */
    private function branchIdsFor(?int $userId): array
    {
        if ($userId === null) {
            return [];
        }

        $user = User::query()->find($userId);

        if ($user === null
            || $user->roles()->where('roles.es_superadmin', true)->exists()
            || $user->can(BranchPermission::GLOBAL_SCOPE)) {
            return [];
        }

        return DB::table('usuario_sucursal')
            ->join('sucursal', 'sucursal.id', '=', 'usuario_sucursal.sucursal_id')
            ->where('usuario_sucursal.usuario_id', $userId)
            ->where('sucursal.activo', true)
            ->orderBy('usuario_sucursal.sucursal_id')
            ->pluck('usuario_sucursal.sucursal_id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();
    }
}
