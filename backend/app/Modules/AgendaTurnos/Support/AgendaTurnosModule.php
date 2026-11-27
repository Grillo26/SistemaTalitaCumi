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

    public const AUDIT_MODULE = 'agenda';

    private function __construct() {}
}
