<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Acciones de bitácora propias del submódulo 02.
 */
final class AgendaAuditAction
{
    public const GENERAR = 'GENERAR';

    public const REGENERAR = 'REGENERAR';

    public const ASIGNAR_PROFESIONAL = 'ASIGNAR_PROFESIONAL';

    public const CREAR_FERIADO = 'CREAR_FERIADO';

    public const ACTUALIZAR_FERIADO = 'ACTUALIZAR_FERIADO';

    public const DESACTIVAR_FERIADO = 'DESACTIVAR_FERIADO';

    private function __construct() {}
}
