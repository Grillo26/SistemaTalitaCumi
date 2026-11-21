<?php

namespace App\Modules\Profesionales\Support;

/**
 * Constantes del módulo 03 — Profesionales.
 */
final class ProfesionalesModule
{
    /**
     * Prefijo público del módulo, compartido con el frontend.
     */
    public const MODULE_URL = '/profesionales';

    /**
     * Supuesto a validar: especialidades secundarias (03/RF-D09, 03/RF-E05).
     *
     * El modelo y la API las soportan; la UI y la validación estricta quedan
     * deshabilitadas hasta que el cliente confirme. Cambiar a true no requiere
     * migración.
     */
    public const SECUNDARIAS_HABILITADAS = false;
}
