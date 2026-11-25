<?php

namespace App\Modules\Pacientes\Support;

/**
 * Motivos de un bloque no disponible de la ficha resumen (04.04).
 */
final class PacienteResumenMotivo
{
    public const MODULO_NO_IMPLEMENTADO = 'MODULO_NO_IMPLEMENTADO';

    public const SIN_SERVICIOS_VIGENTES = 'SIN_SERVICIOS_VIGENTES';

    public const SIN_PERMISO = 'SIN_PERMISO';

    public const SIN_TUTOR = 'SIN_TUTOR';

    public const ERROR = 'ERROR';
}
