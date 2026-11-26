<?php

namespace App\Modules\InscripcionesAtenciones\Support;

/**
 * Constantes del módulo 06 — Inscripciones y atenciones.
 *
 * El submódulo 01 (salas grupales) es el primero; los futuros submódulos
 * reutilizan el prefijo, el provider y el catálogo de auditoría.
 */
final class InscripcionesAtencionesModule
{
    public const MODULE_URL = '/inscripciones-atenciones';

    public const MODULE_URL_SALAS_GRUPALES = self::MODULE_URL.'/salas-grupales';

    public const AUDIT_MODULE = 'inscripciones';

    public const ENTITY_INSCRIPCION_SALA = 'inscripcion_sala';
}
