<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\InscripcionDatosIncompletosException;

/**
 * RN-008: datos obligatorios de la inscripción.
 */
final class InscripcionDatosObligatoriosRule
{
    /**
     * @param  array<string, mixed>  $datos
     */
    public static function assertCompletos(array $datos): void
    {
        $requeridos = [
            'paciente_id',
            'tutor_responsable_id',
            'sucursal_id',
            'sala_id',
            'servicio_id',
            'tarifa_id',
            'categoria_tarifa_id',
            'fecha_inicio',
            'fecha_fin',
        ];

        $faltantes = [];

        foreach ($requeridos as $campo) {
            $valor = $datos[$campo] ?? null;

            if ($valor === null || $valor === '' || (is_int($valor) && $valor <= 0)) {
                $faltantes[] = $campo;
            }
        }

        if ($faltantes !== []) {
            throw new InscripcionDatosIncompletosException($faltantes);
        }
    }
}
