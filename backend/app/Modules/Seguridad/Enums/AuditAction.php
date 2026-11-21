<?php

namespace App\Modules\Seguridad\Enums;

/**
 * Catálogo de acciones auditadas del módulo (RF-B03).
 *
 * Los valores son estables y no se traducen: la UI muestra etiquetas.
 */
final class AuditAction
{
    public const LOGIN = 'INICIAR_SESION';

    public const LOGIN_FAILED = 'LOGIN_FALLIDO';

    public const LOGOUT = 'CERRAR_SESION';

    public const SESSION_EXPIRED = 'SESION_EXPIRADA';

    public const SESSION_REPLACED = 'SESION_REEMPLAZADA';

    public const SESSIONS_INVALIDATED = 'CERRAR_SESIONES';

    public const LOCK = 'BLOQUEAR';

    public const UNLOCK = 'DESBLOQUEAR';

    public const PASSWORD_CHANGED = 'CAMBIAR_PASSWORD';

    public const PASSWORD_RESET = 'RESETEAR_PASSWORD';

    public const CREATE = 'CREAR';

    public const UPDATE = 'EDITAR';

    public const ACTIVATE = 'ACTIVAR';

    public const DEACTIVATE = 'DESACTIVAR';

    public const EDIT_PERMISSIONS = 'EDITAR_PERMISOS';

    public const QUERY = 'CONSULTAR';

    public const EXPORT = 'EXPORTAR';
}
