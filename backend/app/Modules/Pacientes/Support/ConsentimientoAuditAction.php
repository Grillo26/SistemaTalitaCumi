<?php

namespace App\Modules\Pacientes\Support;

/**
 * Acciones de bitácora de consentimientos (04.03 Fase B).
 */
final class ConsentimientoAuditAction
{
    public const GRANT = 'OTORGAR';

    public const DENY = 'NEGAR';

    public const REVOKE = 'REVOCAR';

    public const QUERY = 'CONSULTAR';

    public const CATALOG_CREATE = 'CREAR';

    public const CATALOG_UPDATE = 'ACTUALIZAR';
}
