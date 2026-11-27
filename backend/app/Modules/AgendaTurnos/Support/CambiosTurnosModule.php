<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Constantes del submódulo 03 — cambios de turnos.
 */
final class CambiosTurnosModule
{
    public const MODULE_URL = '/agenda-turnos/cambios-turnos';

    public const AUDIT_MODULE = 'agenda';

    public const ENTITY_TURNO = 'turno';

    public const ENTITY_LOTE_CANCELACION = 'turno_cancelacion_masiva';

    public const ENTITY_MOTIVO = 'motivo_turno';

    private function __construct() {}
}
