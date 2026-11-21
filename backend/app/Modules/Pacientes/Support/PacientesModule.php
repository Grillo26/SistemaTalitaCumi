<?php

namespace App\Modules\Pacientes\Support;

/**
 * Constantes del módulo 04 — Pacientes y tutores.
 */
final class PacientesModule
{
    /**
     * Prefijo público del módulo, compartido con el frontend.
     */
    public const MODULE_URL = '/pacientes';

    public const AUDIT_MODULE = 'pacientes';

    /**
     * 04.02 núcleo implementado en esta entrega: alta, búsqueda y asociación
     * de tutores. La ficha/listado completo y los contactos de emergencia
     * siguen pendientes.
     */
    public const TUTORES_DISPONIBLES = true;

    /**
     * El catálogo de tipos de diagnóstico pertenece al módulo 05.
     * Mientras no exista, el campo es opcional y no se valida contra catálogo.
     */
    public const DIAGNOSTICOS_DISPONIBLES = false;

    /**
     * El filtro/visibilidad por inscripción vigente pertenece al módulo 06.
     */
    public const INSCRIPCIONES_DISPONIBLES = false;

    /**
     * Exportación PDF/Excel pendiente de infraestructura.
     */
    public const EXPORTACION_DISPONIBLE = false;
}
