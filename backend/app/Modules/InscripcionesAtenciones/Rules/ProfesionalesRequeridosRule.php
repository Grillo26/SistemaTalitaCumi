<?php

namespace App\Modules\InscripcionesAtenciones\Rules;

use App\Modules\InscripcionesAtenciones\Exceptions\ProfesionalesInsuficientesException;

/**
 * RN-003 / RN-005 / RN-006 / CA-003.
 *
 * - Sin profesionales: permitido ("Sin asignar").
 * - Con profesionales: cada especialidad requerida debe tener la cantidad
 *   mínima cubierta por profesionales activos de la sucursal.
 */
final class ProfesionalesRequeridosRule
{
    /**
     * @param  list<array{especialidad_id: int, especialidad_nombre: string, cantidad_minima: int}>  $requisitos
     * @param  list<array{id: int, nombre_completo: string, especialidades: list<array{id: int, nombre: string}>}>  $profesionales
     */
    public static function assertCobertura(array $requisitos, array $profesionales): void
    {
        if ($requisitos === [] || $profesionales === []) {
            return;
        }

        $faltantes = [];

        foreach ($requisitos as $requisito) {
            $especialidadId = (int) $requisito['especialidad_id'];
            $requeridos = (int) $requisito['cantidad_minima'];
            $asignados = 0;

            foreach ($profesionales as $profesional) {
                foreach ($profesional['especialidades'] as $especialidad) {
                    if ((int) $especialidad['id'] === $especialidadId) {
                        $asignados++;
                        break;
                    }
                }
            }

            if ($asignados < $requeridos) {
                $faltantes[] = [
                    'especialidad_id' => $especialidadId,
                    'nombre' => (string) $requisito['especialidad_nombre'],
                    'requeridos' => $requeridos,
                    'asignados' => $asignados,
                ];
            }
        }

        if ($faltantes !== []) {
            throw new ProfesionalesInsuficientesException($faltantes);
        }
    }
}
