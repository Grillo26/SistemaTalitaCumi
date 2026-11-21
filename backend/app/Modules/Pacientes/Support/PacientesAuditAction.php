<?php

namespace App\Modules\Pacientes\Support;

/**
 * Acciones de bitácora del módulo 04.
 *
 * Se usan strings estables. No se modifica la clase AuditAction de Seguridad:
 * el catálogo `accion_auditada` y el listener propio son suficientes.
 */
final class PacientesAuditAction
{
    public const CREATE = 'CREAR';

    public const UPDATE = 'ACTUALIZAR';

    public const STATUS_CHANGE = 'CAMBIAR_ESTADO';

    public const QUERY = 'CONSULTAR';

    public const CONFIRM_DUPLICATE = 'CONFIRMAR_DUPLICADO';

    public const DELETE = 'ELIMINAR';

    public const ASSOCIATE_TUTOR = 'ASOCIAR';

    public const DISSOCIATE_TUTOR = 'DESASOCIAR';

    public const CHANGE_PRINCIPAL_TUTOR = 'CAMBIAR_PRINCIPAL';
}
