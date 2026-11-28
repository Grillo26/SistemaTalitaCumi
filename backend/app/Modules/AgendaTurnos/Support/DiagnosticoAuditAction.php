<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Acciones de bitácora propias del submódulo 05.
 */
final class DiagnosticoAuditAction
{
    public const PROGRAMAR = 'PROGRAMAR_DIAGNOSTICO';

    public const ACTUALIZAR_ESTADO = 'ACTUALIZAR_ESTADO_GENERAL';

    public const CANCELAR = 'CANCELAR';

    private function __construct() {}
}
