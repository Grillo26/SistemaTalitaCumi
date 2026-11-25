<?php

namespace App\Modules\Pacientes\Support;

/**
 * Acciones de bitácora de la ficha de ingreso (04.03/RF-011).
 */
final class FichaAuditAction
{
    public const CREATE = 'CREAR';

    public const UPDATE = 'ACTUALIZAR';

    public const COMPLETE = 'COMPLETAR';

    public const ARCHIVE = 'ARCHIVAR';

    public const REACTIVATE = 'REACTIVAR';

    public const QUERY = 'CONSULTAR';

    public const EXPORT = 'EXPORTAR';
}
