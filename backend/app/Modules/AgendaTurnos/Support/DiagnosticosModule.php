<?php

namespace App\Modules\AgendaTurnos\Support;

/**
 * Constantes del submódulo 05 — diagnóstico con varios profesionales.
 */
final class DiagnosticosModule
{
    public const MODULE_URL = '/agenda-turnos/diagnosticos';

    public const AUDIT_MODULE = 'agenda';

    public const ENTITY_DIAGNOSTICO = 'atencion_diagnostico';

    public const ENTITY_TURNO = 'turno';

    public const RECURSO_EXPORTACION = 'diagnosticos_agenda';

    public const RECURSO_CONSULTA = 'diagnostico_coordinacion';

    /** Límite de filas del reporte (PROPUESTO - VALIDAR). */
    public const MAX_FILAS_EXPORTACION = 5000;

    /** Motivo de catálogo para cancelar los turnos futuros del diagnóstico. */
    public const MOTIVO_CANCELACION = 'cancelacion_diagnostico';

    private function __construct() {}
}
