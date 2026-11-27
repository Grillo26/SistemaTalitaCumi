<?php

namespace App\Modules\InscripcionesAtenciones\Support;

/**
 * Constantes del módulo 06 — Inscripciones y atenciones.
 *
 * Submódulos 01 (salas grupales), 02 (paquetes de sesiones individuales),
 * 03 (entrevistas y diagnósticos), 04 (vigencias y renovaciones) y 05
 * (estados de paciente e inscripción); los futuros submódulos reutilizan el
 * prefijo, el provider y el catálogo de auditoría.
 */
final class InscripcionesAtencionesModule
{
    public const MODULE_URL = '/inscripciones-atenciones';

    public const MODULE_URL_SALAS_GRUPALES = self::MODULE_URL.'/salas-grupales';

    public const MODULE_URL_PAQUETES = self::MODULE_URL.'/paquetes-sesiones';

    public const MODULE_URL_ENTREVISTA_DIAGNOSTICO = self::MODULE_URL.'/entrevista-diagnostico';

    public const MODULE_URL_VIGENCIAS = self::MODULE_URL.'/vigencias';

    public const MODULE_URL_ESTADOS = self::MODULE_URL.'/estados';

    public const AUDIT_MODULE = 'inscripciones';

    public const ENTITY_INSCRIPCION_SALA = 'inscripcion_sala';

    public const ENTITY_PAQUETE_SESIONES = 'paquete_sesiones';

    public const ENTITY_ATENCION_ENTREVISTA = 'atencion_entrevista';

    public const ENTITY_ATENCION_DIAGNOSTICO = 'atencion_diagnostico';

    public const ENTITY_RENOVACION_MULTIPLE = 'renovacion_multiple';
}
