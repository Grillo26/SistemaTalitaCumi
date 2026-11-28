<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Constantes del submódulo 04 — registro de asistencia por turno.
 */
final class AsistenciaTurnosModule
{
    public const MODULE_URL = '/agenda-turnos/asistencia';

    public const AUDIT_MODULE = 'agenda';

    public const ENTITY_TURNO = 'turno';

    public const RECURSO_EXPORTACION = 'asistencia_turnos';

    private function __construct() {}
}
