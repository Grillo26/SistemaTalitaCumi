<?php

namespace App\Modules\Pacientes\Support;

/**
 * Permisos del submódulo 04.04 (búsqueda y ficha resumen).
 */
final class PacienteResumenPermission
{
    /**
     * Exportación del listado filtrado a CSV (04.04/RF-013).
     *
     * Se mantiene separado de `paciente.exportar`, que queda reservado para
     * la exportación de la ficha completa de 04.01.
     */
    public const EXPORT_LISTADO = 'paciente.exportar_listado';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::EXPORT_LISTADO,
        ];
    }
}
