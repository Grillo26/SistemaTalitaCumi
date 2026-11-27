<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Constantes del módulo 07 — Agenda y turnos.
 *
 * El submódulo 01 (agenda semanal por sucursal) es de solo consulta. Los
 * submódulos 02 a 05 reutilizan este prefijo, el provider y el modelo Turno.
 */
final class AgendaTurnosModule
{
    public const MODULE_URL = '/agenda-turnos/agenda-semanal';

    public const MODULE_URL_PROGRAMACION = '/agenda-turnos/programacion-turnos';

    public const AUDIT_MODULE = 'agenda';

    public const ENTITY_TURNO = 'turno';

    public const ENTITY_LOTE_GENERACION = 'turno_lote_generacion';

    public const ENTITY_FERIADO = 'feriado';

    private function __construct() {}
}
