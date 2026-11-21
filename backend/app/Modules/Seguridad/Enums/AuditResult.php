<?php

namespace App\Modules\Seguridad\Enums;

/**
 * Resultado de un evento auditado (RF-B01).
 */
enum AuditResult: string
{
    case EXITO = 'exito';
    case FALLO = 'fallo';
    case DENEGADO = 'denegado';

    public function label(): string
    {
        return match ($this) {
            self::EXITO => 'Éxito',
            self::FALLO => 'Fallo',
            self::DENEGADO => 'Denegado',
        };
    }
}
