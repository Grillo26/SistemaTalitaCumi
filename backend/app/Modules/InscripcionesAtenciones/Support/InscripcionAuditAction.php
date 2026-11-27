<?php

namespace App\Modules\InscripcionesAtenciones\Support;

/**
 * Acciones de bitácora propias del módulo 06.
 */
final class InscripcionAuditAction
{
    public const CREATE = 'CREAR';

    public const UPDATE = 'ACTUALIZAR';

    public const RENEW = 'RENOVAR';

    public const WITHDRAW = 'RETIRAR';

    public const PAUSE = 'PAUSAR';

    public const REACTIVATE = 'REACTIVAR';
}
