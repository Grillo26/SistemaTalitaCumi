<?php

namespace App\Modules\Pacientes\Support;

/**
 * Campos sensibles de la ficha de ingreso (04.03/RF-015, 04.03/CA-010).
 *
 * Lista propuesta a validar con el cliente.
 */
final class FichaIngresoFields
{
    /**
     * @var list<string>
     */
    public const SENSITIVE = [
        'motivo_consulta_inicial',
        'composicion_familiar',
        'antecedentes_familiares',
        'observaciones',
        'derivado_por_contacto',
        'motivo_archivado',
    ];

    /**
     * Campos sensibles editables por formulario.
     *
     * @var list<string>
     */
    public const SENSITIVE_EDITABLE = [
        'motivo_consulta_inicial',
        'composicion_familiar',
        'antecedentes_familiares',
        'observaciones',
        'derivado_por_contacto',
    ];
}
