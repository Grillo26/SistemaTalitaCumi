<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\PaqueteDatosIncompletosException;

/**
 * RN-008: datos obligatorios del paquete. Profesional es opcional.
 */
final class PaqueteDatosObligatoriosRule
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
            'servicio_id',
            'tarifa_id',
            'categoria_tarifa_id',
            'cantidad_sesiones',
            'dias_semana',
            'hora',
            'duracion_minutos',
            'fecha_inicio',
            'fecha_fin',
        ];

        $faltantes = [];

        foreach ($requeridos as $campo) {
            $valor = $datos[$campo] ?? null;

            if ($valor === null || $valor === '' || (is_int($valor) && $valor <= 0)) {
                $faltantes[] = $campo;
            }

            if ($campo === 'dias_semana' && is_array($valor) && $valor === []) {
                $faltantes[] = $campo;
            }
        }

        if ($faltantes !== []) {
            throw new PaqueteDatosIncompletosException(array_values(array_unique($faltantes)));
        }
    }
}
