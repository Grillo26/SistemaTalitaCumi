<?php

namespace App\Modules\CatalogoServicios\Support;

/**
 * Constantes del módulo 05 — Catálogo de servicios y tarifas.
 *
 * El submódulo 01 (áreas, servicios y colores) es el primero; los
 * submódulos 02 (tarifas) y 03 (tipos de diagnóstico) reutilizan este
 * prefijo, provider y catálogo de auditoría.
 */
final class CatalogoServiciosModule
{
    public const MODULE_URL = '/catalogo-servicios';

    public const MODULE_URL_TARIFAS = self::MODULE_URL.'/tarifas';

    public const MODULE_URL_CATEGORIAS = self::MODULE_URL.'/categorias-tarifa';

    public const AUDIT_MODULE = 'catalogo_servicios';

    public const CACHE_TTL_SECONDS = 60;
}
