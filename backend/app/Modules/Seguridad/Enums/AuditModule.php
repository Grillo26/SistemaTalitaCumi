<?php

namespace App\Modules\Seguridad\Enums;

/**
 * Módulos lógicos de la bitácora (RF-B01 / RF-B03).
 */
enum AuditModule: string
{
    case AUTENTICACION = 'autenticacion';
    case USUARIOS = 'usuarios';
    case ROLES = 'roles';
    case AUDITORIA = 'auditoria';

    public function label(): string
    {
        return match ($this) {
            self::AUTENTICACION => 'Autenticación',
            self::USUARIOS => 'Usuarios',
            self::ROLES => 'Roles y permisos',
            self::AUDITORIA => 'Auditoría',
        };
    }
}
