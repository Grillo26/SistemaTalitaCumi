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
     * 04.02 Fases A y B implementadas: tutores, asociación, estado,
     * duplicados, enmascarado, auditoría, contactos de emergencia por
     * paciente y exportación CSV de tutores. El PDF y el consumo por el
     * módulo 09 quedan pendientes.
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
     * Contactos de emergencia adicionales por paciente (04.02/RF-012).
     */
    public const CONTACTOS_EMERGENCIA_DISPONIBLES = true;

    /**
     * Ficha de ingreso administrativa (04.03 Fase A).
     */
    public const FICHA_INGRESO_DISPONIBLE = true;

    /**
     * Consentimientos de la ficha de ingreso (04.03 Fase B).
     */
    public const CONSENTIMIENTOS_DISPONIBLES = true;

    /**
     * Exportación PDF/Excel pendiente de infraestructura.
     */
    public const EXPORTACION_DISPONIBLE = false;
}
