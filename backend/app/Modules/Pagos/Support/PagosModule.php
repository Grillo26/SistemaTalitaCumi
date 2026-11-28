<?php

namespace App\Modules\Pagos\Support;

/**
 * Constantes del módulo 08 — Pagos y caja (submódulo 08.01).
 */
final class PagosModule
{
    public const MODULE_URL = '/pagos';

    public const AUDIT_MODULE = 'pagos';

    public const ENTITY_PAGO = 'pago';

    public const ACTION_CREAR = 'CREAR';

    public const MARCA_INICIALIZACION = 'pagos.registro';

    public const LEYENDA_CONFIG_PREFIJO = 'comprobante.prefijo';

    public const LEYENDA_CONFIG_FORMATO = 'comprobante.formato';

    public const CONFIG_ZONA_HORARIA = 'zona_horaria';

    public const CONFIG_MONEDA = 'moneda';
}
