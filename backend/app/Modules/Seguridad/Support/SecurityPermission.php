<?php

namespace App\Modules\Seguridad\Support;

/**
 * Códigos de permiso del módulo de seguridad.
 *
 * El código de aplicación decide por permiso, nunca por nombre de rol.
 * Los nombres de rol solo aparecen en el seeder de la matriz.
 */
final class SecurityPermission
{
    public const USER_VIEW = 'usuario.ver';

    public const USER_CREATE = 'usuario.crear';

    public const USER_EDIT = 'usuario.editar';

    public const USER_CHANGE_STATUS = 'usuario.cambiar_estado';

    public const USER_RESET_PASSWORD = 'usuario.resetear_password';

    public const USER_UNLOCK = 'usuario.desbloquear';

    public const USER_CLOSE_SESSIONS = 'usuario.cerrar_sesiones';

    /** Reservado para RF-U15; la exportación aún no está implementada. */
    public const USER_EXPORT = 'usuario.exportar';

    public const ROLE_VIEW = 'rol.ver';

    public const ROLE_EDIT_PERMISSIONS = 'rol.editar_permisos';

    /** Reservado para RF-R03; la exportación aún no está implementada. */
    public const MATRIX_EXPORT = 'matriz.exportar';

    public const AUDIT_VIEW = 'bitacora.ver';

    /** Reservado para RF-B09; la exportación aún no está implementada. */
    public const AUDIT_EXPORT = 'bitacora.exportar';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::USER_VIEW,
            self::USER_CREATE,
            self::USER_EDIT,
            self::USER_CHANGE_STATUS,
            self::USER_RESET_PASSWORD,
            self::USER_UNLOCK,
            self::USER_CLOSE_SESSIONS,
            self::USER_EXPORT,
            self::ROLE_VIEW,
            self::ROLE_EDIT_PERMISSIONS,
            self::MATRIX_EXPORT,
            self::AUDIT_VIEW,
            self::AUDIT_EXPORT,
        ];
    }

    /**
     * Permisos que el rol Superadmin no puede perder (RF-R08 / RN-16).
     *
     * @return list<string>
     */
    public static function critical(): array
    {
        return [
            self::ROLE_VIEW,
            self::ROLE_EDIT_PERMISSIONS,
            self::USER_VIEW,
            self::USER_CREATE,
            self::USER_EDIT,
            self::USER_CHANGE_STATUS,
            self::USER_RESET_PASSWORD,
            self::USER_UNLOCK,
            self::USER_CLOSE_SESSIONS,
            self::AUDIT_VIEW,
        ];
    }
}
